<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ResignRequest;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ResignController extends Controller
{
    public function create()
    {
        $user = $this->getAuthUser();

        if (!$user->isKaryawan()) {
            return redirect()->route('dashboard');
        }

        // Cek apakah sudah ada pengajuan yang masih pending
        $existingRequest = ResignRequest::where('employees_id', $user->id)
            ->whereNotIn('workflow_stage', [
                ResignRequest::STAGE_COMPLETED,
                ResignRequest::STAGE_PM_REJECTED,
                ResignRequest::STAGE_HC_REJECTED,
            ])
            ->latest('created_at')
            ->first();

        $hasPm = !empty($user->pm_id);
        $employmentType = $this->resolveEmploymentType($user);
        $requiresResignFile = $this->requiresResignLetterUpload($employmentType);
        $leaveBalance = $this->fetchLeaveBalance($user);

        return view('resign.create', compact('user', 'existingRequest', 'hasPm', 'employmentType', 'requiresResignFile', 'leaveBalance'));
    }

    public function submit(Request $request)
    {
        $user = $this->getAuthUser();

        if (!$user->isKaryawan()) {
            return redirect()->route('dashboard');
        }

        $activeRequest = ResignRequest::where('employees_id', $user->id)
            ->whereNotIn('workflow_stage', [
                ResignRequest::STAGE_COMPLETED,
                ResignRequest::STAGE_PM_REJECTED,
                ResignRequest::STAGE_HC_REJECTED,
            ])
            ->latest('created_at')
            ->first();

        if ($activeRequest) {
            return redirect()
                ->route('resign.create')
                ->with('error', 'Masih ada pengajuan resign yang sedang diproses. Selesaikan pengajuan sebelumnya terlebih dahulu.');
        }

        $employmentType = $this->resolveEmploymentType($user);
        $requiresResignFile = $this->requiresResignLetterUpload($employmentType);

        $request->validate([
            'alasan'           => 'required|string|min:50',
            'tanggal_berhenti' => 'required|date|after:today',
            'resign_file'      => ($requiresResignFile ? 'required' : 'nullable') . '|file|mimes:pdf,doc,docx|mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document|max:5120',
        ], [
            'alasan.min'              => 'Alasan minimal 50 karakter.',
            'tanggal_berhenti.after'  => 'Tanggal terakhir harus setelah hari ini.',
            'resign_file.required'    => 'File surat resign wajib diupload.',
            'resign_file.mimes'       => 'File harus berformat PDF, DOC, atau DOCX.',
            'resign_file.mimetypes'   => 'Konten file tidak valid. Upload hanya PDF, DOC, atau DOCX.',
            'resign_file.max'         => 'Ukuran file maksimal 5MB.',
        ]);

        $fileData = ['path' => null, 'original_name' => null];
        $fileMethod = 'manual';
        if ($request->hasFile('resign_file')) {
            $fileData = $this->storeResignFile($request, $user);
            $fileMethod = 'upload';
        }

        // Tentukan workflow stage
        $stage = $user->pm_id ? ResignRequest::STAGE_TO_PM : ResignRequest::STAGE_TO_HC_APPROVAL;

        $resign = ResignRequest::create([
            'employees_id'       => $user->id,
            'resign_file_method' => $fileMethod,
            'reason'             => $request->alasan,
            'last_date'          => $request->tanggal_berhenti,
            'description'        => $request->deskripsi,
            'resign_file_path'   => $fileData['path'],
            'resign_filename'    => $fileData['original_name'],
            'status'             => ResignRequest::STATUS_PENDING,
            'workflow_stage'     => $stage,
            'created_at'         => now(),
            'created_by'         => $user->id,
        ]);

        // Kirim notifikasi
        if ($user->pm_id) {
            // Notifikasi ke PM
            Notification::send(
                $user->pm_id,
                'resign_submitted',
                'Pengajuan Resign Baru',
                $user->nama . ' mengajukan resign. Mohon segera diproses.',
                ['resign_id' => $resign->id]
            );
        } else {
            // Langsung ke HC jika tidak ada PM
            $hcUsers = User::where('role', 'hc')->get();
            foreach ($hcUsers as $hc) {
                Notification::send(
                    $hc->id,
                    'resign_submitted',
                    'Pengajuan Resign Baru',
                    $user->nama . ' mengajukan resign (tanpa PM). Mohon segera diverifikasi.',
                    ['resign_id' => $resign->id]
                );
            }
        }

        return redirect()->route('dashboard')
            ->with('success', 'Pengajuan resign berhasil dikirim.')
            ->with('redirect_after_toast', route('resign.detail', $resign->id));
    }

    public function detail(int $id)
    {
        $user = $this->getAuthUser();
        $resign = ResignRequest::with(['employee', 'approvedBy', 'approvedHcBy', 'rejectedBy', 'checklistItems'])->findOrFail($id);

        // Validasi akses
        $canView = $resign->employees_id === $user->id
            || $user->isAdmin()
            || $user->isHc()
            || $user->canAccessChecklist()
            || $this->canPmAccessResign($user, $resign);

        if (!$canView) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $progress = null;
        if ($resign->isInChecklistStage() || $resign->isCompleted()) {
            $progress = $resign->getChecklistProgress();
        }

        return view('resign.detail', compact('user', 'resign', 'progress'));
    }

    public function edit(int $id)
    {
        $user = $this->getAuthUser();
        $resign = ResignRequest::findOrFail($id);

        if ($resign->employees_id !== $user->id || $resign->status !== ResignRequest::STATUS_PENDING) {
            abort(403, 'Tidak dapat mengedit pengajuan ini.');
        }

        return view('resign.edit', compact('user', 'resign'));
    }

    public function update(Request $request, int $id)
    {
        $user = $this->getAuthUser();
        $resign = ResignRequest::findOrFail($id);

        if ($resign->employees_id !== $user->id || $resign->status !== ResignRequest::STATUS_PENDING) {
            abort(403, 'Tidak dapat mengedit pengajuan ini.');
        }

        $request->validate([
            'alasan'           => 'required|string|min:50',
            'tanggal_berhenti' => 'required|date|after:today',
        ], [
            'alasan.required'          => 'Alasan pengunduran diri wajib diisi.',
            'alasan.min'               => 'Alasan pengunduran diri minimal 50 karakter.',
            'tanggal_berhenti.required'=> 'Tanggal terakhir bekerja wajib diisi.',
            'tanggal_berhenti.after'   => 'Tanggal terakhir bekerja harus setelah hari ini.',
        ]);

        $data = [
            'reason'     => $request->alasan,
            'last_date'  => $request->tanggal_berhenti,
            'description'=> $request->deskripsi,
            'updated_at' => now(),
            'updated_by' => $user->id,
        ];

        // Update file jika ada upload baru
        if ($request->hasFile('resign_file')) {
            $request->validate([
                'resign_file' => 'file|mimes:pdf,doc,docx|mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document|max:5120',
            ], [
                'resign_file.mimes'     => 'File harus berformat PDF, DOC, atau DOCX.',
                'resign_file.mimetypes' => 'Konten file tidak valid. Upload hanya PDF, DOC, atau DOCX.',
                'resign_file.max'       => 'Ukuran file maksimal 5MB.',
            ]);

            $fileData = $this->storeResignFile($request, $user);

            if (!empty($resign->resign_file_path)) {
                Storage::disk('public')->delete($resign->resign_file_path);
            }

            $data['resign_file_path'] = $fileData['path'];
            $data['resign_filename']  = $fileData['original_name'];
        }

        $resign->update($data);

        return redirect()->route('resign.detail', $id)->with('success', 'Pengajuan berhasil diperbarui.');
    }

    public function cancel(Request $request, int $id)
    {
        $user = $this->getAuthUser();
        $resign = ResignRequest::findOrFail($id);

        if ($resign->employees_id !== $user->id || $resign->status !== ResignRequest::STATUS_PENDING) {
            abort(403, 'Tidak dapat membatalkan pengajuan ini.');
        }

        if (!empty($resign->resign_file_path)) {
            Storage::disk('public')->delete($resign->resign_file_path);
        }

        $resign->delete();

        return redirect()->route('dashboard')->with('success', 'Pengajuan resign berhasil dibatalkan.');
    }

    public function listAll()
    {
        $user = $this->getAuthUser();

        if (!$user->isAdmin() && !$user->isPm() && !$user->isHc()) {
            abort(403);
        }

        $query = ResignRequest::with('employee')->orderBy('created_at', 'desc');

        // PM non-admin hanya boleh melihat pengajuan resign bawahan langsungnya,
        // kecuali jika dia termasuk divisi checklist (HC/IT/DOC/Finance/GA) → boleh lihat semua.
        if ($user->isPm() && !$user->isAdmin() && !$user->isHc() && !$user->canAccessChecklist()) {
            $subordinateIds = User::where('pm_id', $user->id)->pluck('id');
            $query->where(function ($q) use ($subordinateIds, $user) {
                $q->whereIn('employees_id', $subordinateIds)
                    ->orWhere('employees_id', $user->id);
            });
        }

        // Special-case HC observer: saat membuka list, notifikasi dashboard dianggap sudah dibaca.
        if ($user->isReadonlyHcObserver()) {
            $latestActiveCreatedAt = ResignRequest::where('employees_id', '!=', $user->id)
                ->whereNotIn('workflow_stage', [
                    ResignRequest::STAGE_COMPLETED,
                    ResignRequest::STAGE_PM_REJECTED,
                    ResignRequest::STAGE_HC_REJECTED,
                ])
                ->max('created_at');

            if ($latestActiveCreatedAt) {
                session(['readonly_hc_seen_until' => $latestActiveCreatedAt]);
            } else {
                session()->forget('readonly_hc_seen_until');
            }
        }

        $resigns = $query->get();

        return view('resign.list', compact('user', 'resigns'));
    }

    private function canPmAccessResign(User $pm, ResignRequest $resign): bool
    {
        if (!$pm->isPm()) {
            return false;
        }

        return User::where('id', $resign->employees_id)
            ->where('pm_id', $pm->id)
            ->exists();
    }

    private function storeResignFile(Request $request, User $user): array
    {
        $file = $request->file('resign_file');
        $extension = strtolower($file->extension());
        if ($extension === '') {
            $extension = strtolower($file->getClientOriginalExtension());
        }

        $filename = 'resign_' . $user->id . '_' . time() . '_' . uniqid() . '.' . $extension;
        $path = $file->storeAs('uploads/resign_letters', $filename, 'public');

        return [
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
        ];
    }

    private function requiresResignLetterUpload(?string $employmentType): bool
    {
        // Hanya kontrak dan magang yang tidak wajib upload surat resign.
        if ($employmentType !== null && in_array($employmentType, ['kontrak', 'magang'], true)) {
            return false;
        }
        // Permanen / tetap / atau status tidak terbaca: wajib upload surat resign.
        return true;
    }

    /**
     * Ambil employees_id dari net_hrd.employees yang sesuai dengan user (cocokkan via id_karyawan, nik, atau username).
     * API leave baca dari net_hrd.employees, jadi ID yang dikirim harus dari tabel ini.
     */
    private function getNetHrdEmployeesId(User $user): ?int
    {
        try {
            if (!Schema::connection('net_hrd')->hasTable('employees')) {
                return null;
            }
            if (empty($user->id_karyawan) && empty($user->username)) {
                return null;
            }
            $cols = Schema::connection('net_hrd')->getColumnListing('employees');
            $idCol = in_array('id', $cols, true) ? 'id' : (in_array('employees_id', $cols, true) ? 'employees_id' : null);
            if (!$idCol) {
                return null;
            }

            $q = DB::connection('net_hrd')->table('employees');
            $q->where(function ($sub) use ($cols, $user) {
                if (in_array('id_karyawan', $cols, true) && $user->id_karyawan !== null && $user->id_karyawan !== '') {
                    $sub->orWhere('id_karyawan', $user->id_karyawan);
                }
                if (in_array('nik', $cols, true) && $user->id_karyawan !== null && $user->id_karyawan !== '') {
                    $sub->orWhere('nik', $user->id_karyawan);
                }
                if (in_array('username', $cols, true) && $user->username !== null && $user->username !== '') {
                    $sub->orWhere('username', $user->username);
                }
            });

            $row = $q->select($idCol)->first();
            return $row && isset($row->{$idCol}) ? (int) $row->{$idCol} : null;
        } catch (\Throwable $e) {
            Log::channel('stack')->debug('Leave balance: getNetHrdEmployeesId exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Ambil sisa cuti karyawan dari API MUC Leave (balance-remaining).
     * API mengambil data dari database net_hrd tabel employees; employees_id harus ID dari net_hrd.employees, bukan dari users.
     */
    private function fetchLeaveBalance(User $user): ?array
    {
        $url = config('services.muc_leave.url');
        $token = config('services.muc_leave.token');
        if (empty($url) || empty($token)) {
            Log::channel('stack')->debug('Leave balance: URL or token empty', ['url' => $url ? 'set' : 'empty', 'token_len' => $token ? strlen($token) : 0]);
            return null;
        }

        // API baca dari net_hrd.employees → kirim employees_id dari tabel itu, bukan dari users
        $employeesId = $this->getNetHrdEmployeesId($user);
        if ($employeesId === null) {
            Log::channel('stack')->debug('Leave balance: karyawan tidak ditemukan di net_hrd.employees', ['user_id' => $user->id, 'id_karyawan' => $user->id_karyawan]);
            return null;
        }

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ];
        $payload = ['employees_id' => $employeesId];

        try {
            // Spek API: body JSON; coba POST dulu, lalu GET. Jika 404 coba URL alternatif (leave vs leavel).
            $response = Http::withHeaders($headers)->post($url, $payload);

            if (!$response->successful()) {
                $response = Http::withHeaders($headers)->get($url, $payload);
            }

            if (!$response->successful() && $response->status() === 404) {
                $altUrl = preg_replace('#/leavel/#', '/leave/', $url);
                if ($altUrl !== $url) {
                    $response = Http::withHeaders($headers)->post($altUrl, $payload);
                    if (!$response->successful()) {
                        $response = Http::withHeaders($headers)->get($altUrl, $payload);
                    }
                }
            }

            if (!$response->successful()) {
                Log::channel('stack')->warning('Leave balance API error', ['status' => $response->status(), 'body' => $response->body(), 'employees_id' => $employeesId]);
                return null;
            }

            $json = $response->json();
            if (($json['status'] ?? '') !== 'success' || !isset($json['data'])) {
                Log::channel('stack')->warning('Leave balance API: unexpected response', ['json' => $json]);
                return null;
            }

            $data = $json['data'];
            return [
                'status' => (bool) ($data['status'] ?? false),
                'sisa_cuti' => isset($data['sisa_cuti']) ? (float) $data['sisa_cuti'] : null,
                'total_max_leave' => isset($data['total_max_leave']) ? (float) $data['total_max_leave'] : null,
                'max_leave' => isset($data['max_leave']) ? (int) $data['max_leave'] : null,
                'threshold' => isset($data['threshold']) ? (int) $data['threshold'] : null,
            ];
        } catch (\Throwable $e) {
            Log::channel('stack')->warning('Leave balance API exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    private function resolveEmploymentType(User $user): ?string
    {
        try {
            if (!Schema::connection('net_hrd')->hasTable('employees_status')) {
                return null;
            }
            if (!Schema::connection('net_hrd')->hasTable('status')) {
                return null;
            }

            $employeeStatusCols = Schema::connection('net_hrd')->getColumnListing('employees_status');
            $statusCols = Schema::connection('net_hrd')->getColumnListing('status');
        } catch (\Throwable $e) {
            return null;
        }

        $joinFromEmployeeStatus = collect(['status_id', 'id_status'])->first(fn ($col) => in_array($col, $employeeStatusCols, true));
        $joinFromStatus = collect(['id', 'status_id'])->first(fn ($col) => in_array($col, $statusCols, true));
        if (!$joinFromEmployeeStatus || !$joinFromStatus) {
            return null;
        }

        $statusTextCol = collect(['status', 'name', 'nama_status', 'keterangan', 'description'])
            ->first(fn ($col) => in_array($col, $statusCols, true));
        if (!$statusTextCol) {
            return null;
        }

        // employees_status.employees_id biasanya merujuk ke net_hrd.employees.id, bukan users.id, bukan users.id
        $netHrdEmployeeId = $this->getNetHrdEmployeesId($user);
        if ($netHrdEmployeeId !== null && in_array('employees_id', $employeeStatusCols, true)) {
            $row = DB::connection('net_hrd')
                ->table('employees_status as es')
                ->join('status as s', "es.{$joinFromEmployeeStatus}", '=', "s.{$joinFromStatus}")
                ->when(in_array('is_active', $employeeStatusCols, true), fn ($q) => $q->where('es.is_active', 1))
                ->where('es.employees_id', $netHrdEmployeeId)
                ->orderByDesc('es.' . (in_array('id', $employeeStatusCols, true) ? 'id' : 'employees_id'))
                ->selectRaw("s.{$statusTextCol} as status_text")
                ->first();
            if ($row && isset($row->status_text)) {
                return $this->mapStatusTextToType($row->status_text);
            }
        }

        $identityColumns = collect(['employees_id', 'employee_id', 'id_employee', 'id_karyawan', 'nik', 'username'])
            ->filter(fn ($col) => in_array($col, $employeeStatusCols, true))
            ->values();
        if ($identityColumns->isEmpty()) {
            return null;
        }

        $identityCandidates = array_values(array_filter([
            $netHrdEmployeeId,
            $user->id,
            $user->id_karyawan,
            $user->username,
        ], fn ($v) => $v !== null && $v !== ''));

        if (empty($identityCandidates)) {
            return null;
        }

        $row = DB::connection('net_hrd')
            ->table('employees_status as es')
            ->join('status as s', "es.{$joinFromEmployeeStatus}", '=', "s.{$joinFromStatus}")
            ->when(in_array('is_active', $employeeStatusCols, true), fn ($q) => $q->where('es.is_active', 1))
            ->where(function ($q) use ($identityColumns, $identityCandidates) {
                foreach ($identityColumns as $col) {
                    foreach ($identityCandidates as $value) {
                        $q->orWhere("es.{$col}", $value);
                    }
                }
            })
            ->orderByDesc('es.' . (in_array('id', $employeeStatusCols, true) ? 'id' : $joinFromEmployeeStatus))
            ->selectRaw("s.{$statusTextCol} as status_text")
            ->first();

        if (!$row || !isset($row->status_text)) {
            return null;
        }

        return $this->mapStatusTextToType($row->status_text);
    }

    private function mapStatusTextToType(string $statusText): ?string
    {
        $text = strtolower((string) $statusText);
        if (str_contains($text, 'magang') || str_contains($text, 'intern') || str_contains($text, 'pkl') || str_contains($text, 'prakerin') || str_contains($text, 'praktik kerja') || str_contains($text, 'apprentice')) {
            return 'magang';
        }
        if (str_contains($text, 'kontrak') || str_contains($text, 'contract') || str_contains($text, 'pkwt') || str_contains($text, 'perjanjian waktu tertentu') || str_contains($text, 'outsourcing') || str_contains($text, 'alih daya')) {
            return 'kontrak';
        }
        if (str_contains($text, 'permanen') || str_contains($text, 'tetap')) {
            return 'permanen';
        }
        return null;
    }
}
