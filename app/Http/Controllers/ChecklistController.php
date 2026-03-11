<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\ResignRequest;
use App\Models\ResignChecklistItem;
use App\Models\Notification;
use App\Models\ResignFile;
use App\Models\User;

class ChecklistController extends Controller
{
    private const CHECKLIST_REQUIRED_MSG = 'Checklist wajib diisi Keterangan sebelum disimpan maupun dicentang.';

    public function index(Request $request)
    {
        $user = $this->getAuthUser();

        if (!$user->canAccessChecklist()) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $dept = $user->getDepartment();
        $resignId = $request->get('id');

        $query = ResignRequest::where('workflow_stage', ResignRequest::STAGE_TO_HC)
            ->where('employees_id', '!=', $user->id)
            ->with(['employee', 'checklistItems' => function ($q) use ($dept) {
                $q->where('department', $dept)->with('doneByUser')->orderBy('item_key');
            }]);

        $query->with(['files' => function ($q) {
            $q->orderBy('created_at', 'desc');
        }]);

        if ($resignId) {
            $query->where('id', $resignId);
        }

        $resigns = $query->orderBy('created_at', 'desc')->get();

        // Generate checklist items jika belum ada
        foreach ($resigns as $r) {
            ResignChecklistItem::createDefaultItems($r->id);
            // Reload items setelah generate
            $r->load(['checklistItems' => function ($q) use ($dept) {
                $q->where('department', $dept)->with('doneByUser')->orderBy('item_key');
            }]);
        }

        // Mark checklist sebagai sudah dilihat
        session(['checklist_seen_until' => now()]);
        
        return view('checklist.index', compact('user', 'resigns', 'dept'));
    }

    public function markSeen(Request $request)
    {
        $user = $this->getAuthUser();
        if (!$user->canAccessChecklist()) {
            abort(403);
        }
        session(['checklist_seen_until' => now()]);
        return response()->json(['success' => true]);
    }

    public function update(Request $request)
    {
        $user = $this->getAuthUser();

        if (!$user->canAccessChecklist()) {
            abort(403);
        }

        $request->validate([
            'resign_request_id' => 'required|integer',
            'items'             => 'required|array',
        ]);

        $dept = $user->getDepartment();
        $resignId = (int) $request->resign_request_id;

        $resign = ResignRequest::with('employee')->findOrFail($resignId);

        // Hardening: user tidak boleh memproses checklist untuk pengajuan resign miliknya sendiri.
        if ((int) $resign->employees_id === (int) $user->id) {
            $message = 'Anda tidak dapat memproses checklist pengajuan resign milik sendiri.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }
            return redirect()->route('checklist.index')->with('error', $message);
        }

        if (!$resign->isInChecklistStage()) {
            return response()->json(['success' => false, 'message' => 'Pengajuan tidak dalam tahap checklist.'], 422);
        }

        // Update setiap item checklist
        foreach ($request->items as $itemKey => $itemData) {
            $item = ResignChecklistItem::where('resign_request_id', $resignId)
                ->where('department', $dept)
                ->where('item_key', $itemKey)
                ->first();

            if (!$item) continue;

            $done = !empty($itemData['done']);
            $keterangan = trim((string) ($itemData['keterangan'] ?? ''));

            if ($done && $keterangan === '') {
                $message = self::CHECKLIST_REQUIRED_MSG;
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                return back()->withErrors(['items' => $message])->withInput();
            }

            $payload = [
                'done'       => $done,
                'done_at'    => $done ? ($item->done_at ?? now()) : null,
                'pic'        => $item->pic,
                'pj'         => $itemData['pj'] ?? $item->pj,
                'keterangan' => $keterangan !== '' ? $keterangan : null,
            ];
            if (Schema::hasColumn($item->getTable(), 'done_by')) {
                // Set done_by hanya jika belum pernah di-set atau jika di-uncheck (set null)
                if ($done && !$item->done_by) {
                    $payload['done_by'] = $user->id;
                } elseif (!$done) {
                    $payload['done_by'] = null;
                }
                // Jika sudah done dan sudah ada done_by, pertahankan (tidak di-overwrite)
            }
            $item->update($payload);
        }

        // Cek apakah semua checklist sudah selesai
        $allDone = ResignChecklistItem::where('resign_request_id', $resignId)
            ->where('done', 0)
            ->doesntExist();
        // NOTE: Jangan auto-complete saat checklist terakhir disimpan.
        // Kolom completed_{dept}_at/by hanya diisi lewat tombol "Complete".

        if ($request->ajax()) {
            $savedBy = $user->nama ?? $user->username;
            $msg = $allDone ? 'Semua item checklist sudah dicentang.' : 'Checklist berhasil disimpan.';
            return response()->json([
                'success'      => true,
                'all_done'     => $allDone,
                'message'      => $msg,
                'saved_by'     => $savedBy,
                'redirect_url' => route('checklist.index'),
            ]);
        }

        return redirect()->route('checklist.index')
            ->with('success', $allDone ? 'Semua checklist selesai!' : 'Checklist berhasil disimpan.');
    }

    public function uploadAttachment(Request $request): RedirectResponse
    {
        $user = $this->getAuthUser();
        if (!$user->canAccessChecklist()) {
            abort(403);
        }

        $validated = $request->validate([
            'resign_request_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'file' => 'required|file|max:10240',
        ], [
            'file.required' => 'File wajib dipilih.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        $resignId = (int) $validated['resign_request_id'];
        $resign = ResignRequest::with('employee')->findOrFail($resignId);
        if (!$resign->isInChecklistStage() && !$resign->isCompleted()) {
            return redirect()->route('checklist.index')->with('error', 'Pengajuan tidak dalam tahap checklist.');
        }

        $file = $request->file('file');
        $ext = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $safeExt = $ext !== '' ? $ext : 'bin';
        $dept = (string) ($user->getDepartment() ?? 'dept');
        $filename = 'attach_' . $dept . '_' . $resignId . '_' . time() . '_' . uniqid() . '.' . $safeExt;
        $path = $file->storeAs('uploads/checklist_attachments', $filename, 'public');

        ResignFile::create([
            'resign_request_id' => $resignId,
            'title' => $validated['title'],
            'filename' => $file->getClientOriginalName(),
            'filepath' => $path,
            'created_at' => now(),
            'created_by' => $user->id,
            'updated_at' => now(),
            'updated_by' => $user->id,
        ]);

        return redirect()->route('checklist.index')->with('success', 'Attachment berhasil diupload.');
    }

    // Backward compatible alias (dulunya khusus doc)
    public function uploadDocAttachment(Request $request): RedirectResponse
    {
        return $this->uploadAttachment($request);
    }

    public function completeDivision(Request $request): RedirectResponse
    {
        $user = $this->getAuthUser();
        if (!$user->canAccessChecklist()) {
            abort(403);
        }

        $dept = (string) $user->getDepartment();
        $validated = $request->validate([
            'resign_request_id' => 'required|integer',
        ]);
        $resignId = (int) $validated['resign_request_id'];
        $resign = ResignRequest::with('employee')->findOrFail($resignId);
        if (!$resign->isInChecklistStage() && !$resign->isCompleted()) {
            return redirect()->route('checklist.index')->with('error', 'Pengajuan tidak dalam tahap checklist.');
        }

        $deptAllDone = ResignChecklistItem::where('resign_request_id', $resignId)
            ->where('department', $dept)
            ->where('done', 0)
            ->doesntExist();
        if (!$deptAllDone) {
            return redirect()->route('checklist.index')->with('error', 'Checklist divisi belum selesai. Pastikan semua item sudah dicentang.');
        }

        $divisionJustCompleted = $this->markDivisionCompletedIfAllDone($resign, $dept, (int) $user->id, true);
        if ($divisionJustCompleted) {
            $this->notifyDivisionCompleted($resign, $dept);
            if ($this->areAllDivisionsCompleted($resign)) {
                $this->notifyAllDivisionsCompletedToHc($resign);
            }
        }
        return redirect()->route('checklist.index')->with('success', 'Checklist divisi berhasil ditandai complete.');
    }

    public function uploadHcSuratKeterangan(Request $request): RedirectResponse
    {
        $user = $this->getAuthUser();
        if (!$user->canAccessChecklist() || $user->getDepartment() !== 'hc') {
            abort(403);
        }

        $validated = $request->validate([
            'resign_request_id' => 'required|integer',
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx',
        ], [
            'file.required' => 'File surat keterangan wajib dipilih.',
            'file.mimes' => 'Format surat keterangan harus PDF/DOC/DOCX.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        $resignId = (int) $validated['resign_request_id'];
        $resign = ResignRequest::findOrFail($resignId);
        if (!$resign->isInChecklistStage() && !$resign->isCompleted()) {
            return redirect()->route('checklist.index')->with('error', 'Pengajuan tidak dalam tahap checklist.');
        }

        if (!$this->areAllDivisionsCompleted($resign)) {
            $incomplete = $this->getIncompleteDivisionLabels($resign);
            $msg = 'Belum semua divisi complete.';
            if ($incomplete !== []) {
                $msg .= ' Divisi yang belum: ' . implode(', ', $incomplete) . '.';
            }
            return redirect()->route('checklist.index')->with('error', $msg);
        }

        $file = $request->file('file');
        $ext = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $safeExt = $ext !== '' ? $ext : 'bin';
        $filename = 'surat_keterangan_' . $resignId . '_' . time() . '_' . uniqid() . '.' . $safeExt;
        $path = $file->storeAs('uploads/surat_keterangan', $filename, 'public');

        ResignFile::create([
            'resign_request_id' => $resignId,
            'title' => 'Surat Keterangan',
            'filename' => $file->getClientOriginalName(),
            'filepath' => $path,
            'created_at' => now(),
            'created_by' => $user->id,
            'updated_at' => now(),
            'updated_by' => $user->id,
        ]);

        return redirect()->route('checklist.index')->with('success', 'Surat keterangan berhasil diupload.');
    }

    public function hcDone(Request $request): RedirectResponse
    {
        $user = $this->getAuthUser();
        if (!$user->canAccessChecklist() || $user->getDepartment() !== 'hc') {
            abort(403);
        }

        $validated = $request->validate([
            'resign_request_id' => 'required|integer',
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx',
        ], [
            'file.required' => 'File Surat Keterangan wajib dipilih.',
            'file.mimes' => 'Format Surat Keterangan harus PDF/DOC/DOCX.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        $resignId = (int) $validated['resign_request_id'];
        $resign = ResignRequest::with('employee')->findOrFail($resignId);
        if (!$resign->isInChecklistStage() && !$resign->isCompleted()) {
            return redirect()->route('checklist.index')->with('error', 'Pengajuan tidak dalam tahap checklist.');
        }

        if (!$this->areAllDivisionsCompleted($resign)) {
            $incomplete = $this->getIncompleteDivisionLabels($resign);
            $msg = 'Belum semua divisi complete.';
            if ($incomplete !== []) {
                $msg .= ' Divisi yang belum: ' . implode(', ', $incomplete) . '.';
            }
            return redirect()->route('checklist.index')->with('error', $msg);
        }

        // Simpan Surat Keterangan dari file yang dikirim bersama Done
        $file = $request->file('file');
        $ext = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $safeExt = $ext !== '' ? $ext : 'bin';
        $filename = 'surat_keterangan_' . $resignId . '_' . time() . '_' . uniqid() . '.' . $safeExt;
        $path = $file->storeAs('uploads/surat_keterangan', $filename, 'public');
        ResignFile::create([
            'resign_request_id' => $resignId,
            'title' => 'Surat Keterangan',
            'filename' => $file->getClientOriginalName(),
            'filepath' => $path,
            'created_at' => now(),
            'created_by' => $user->id,
            'updated_at' => now(),
            'updated_by' => $user->id,
        ]);

        // Finalisasi: set done_at/done_by dan status menjadi done
        $resign->update([
            'done_at' => now(),
            'done_by' => $user->id,
            'workflow_stage' => ResignRequest::STAGE_COMPLETED,
            'status' => ResignRequest::STATUS_DONE,
        ]);

        Notification::send(
            $resign->employees_id,
            'resign_completed',
            'Proses Resign Selesai',
            'Semua divisi telah menyelesaikan checklist dan HC telah memfinalisasi pengajuan resign Anda.',
            ['resign_id' => $resign->id]
        );

        $this->notifyHcFinalizedToAllDivisions($resign);

        return redirect()->route('checklist.index')->with('success', 'Pengajuan berhasil difinalisasi (Done).');
    }

    private function markDivisionCompletedIfAllDone(ResignRequest $resign, string $dept, int $userId, bool $force = false): bool
    {
        $map = [
            'hc' => ['completed_hc_at', 'completed_hc_by'],
            'it' => ['completed_it_at', 'completed_it_by'],
            'doc' => ['completed_doc_at', 'completed_doc_by'],
            'finance' => ['completed_finance_at', 'completed_finance_by'],
            'ga' => ['completed_ga_at', 'completed_ga_by'],
        ];
        if (!isset($map[$dept])) {
            return false;
        }

        [$atCol, $byCol] = $map[$dept];
        if (!Schema::hasColumn($resign->getTable(), $atCol) || !Schema::hasColumn($resign->getTable(), $byCol)) {
            return false;
        }

        $deptAllDone = ResignChecklistItem::where('resign_request_id', $resign->id)
            ->where('department', $dept)
            ->where('done', 0)
            ->doesntExist();
        if (!$deptAllDone) {
            return false;
        }

        if (!$force && !empty($resign->{$atCol})) {
            return false;
        }

        $ts = now();
        $resign->update([
            $atCol => $ts,
            $byCol => $userId,
        ]);
        // Refresh in-memory values for subsequent checks
        $resign->{$atCol} = $ts;
        $resign->{$byCol} = $userId;
        return true;
    }

    private function areAllDivisionsCompleted(ResignRequest $resign): bool
    {
        foreach (['hc', 'it', 'doc', 'finance', 'ga'] as $dept) {
            $col = "completed_{$dept}_at";
            if (!Schema::hasColumn($resign->getTable(), $col)) {
                return false;
            }
            if (empty($resign->{$col})) {
                return false;
            }
        }
        return true;
    }

    /** @return array<int, string> Daftar nama divisi yang belum complete */
    private function getIncompleteDivisionLabels(ResignRequest $resign): array
    {
        $labels = ResignChecklistItem::DEPARTMENT_LABELS;
        $incomplete = [];
        foreach (['hc', 'it', 'doc', 'finance', 'ga'] as $dept) {
            $col = "completed_{$dept}_at";
            if (Schema::hasColumn($resign->getTable(), $col) && empty($resign->{$col})) {
                $incomplete[] = $labels[$dept] ?? strtoupper($dept);
            }
        }
        return $incomplete;
    }

    private function notifyDivisionCompleted(ResignRequest $resign, string $dept): void
    {
        $deptLabels = ResignChecklistItem::DEPARTMENT_LABELS;
        $label = $deptLabels[$dept] ?? strtoupper($dept);
        $employeeName = $resign->employee->nama ?? 'karyawan';

        $recipients = $this->getChecklistDivisionRecipients();
        Log::info('Notify division completed', [
            'dept' => $dept,
            'resign_id' => $resign->id,
            'recipients' => $recipients->count(),
        ]);
        foreach ($recipients as $recipient) {
            Notification::send(
                $recipient->id,
                'division_checklist_completed',
                'Checklist Divisi Selesai',
                "Divisi {$label} telah menyelesaikan checklist untuk {$employeeName}.",
                ['resign_id' => $resign->id, 'division' => $dept, 'employee_name' => $employeeName]
            );
        }
    }

    private function notifyAllDivisionsCompletedToHc(ResignRequest $resign): void
    {
        $employeeName = $resign->employee->nama ?? 'karyawan';
        $hcUsers = User::where('role', 'hc')->get();
        foreach ($hcUsers as $hc) {
            Notification::send(
                $hc->id,
                'all_divisions_completed',
                'Semua Divisi Selesai Checklist',
                "Semua divisi telah menyelesaikan checklist pengajuan \"{$employeeName}\". Silakan upload Surat Keterangan dan klik Done.",
                ['resign_id' => $resign->id, 'employee_name' => $employeeName]
            );
        }
    }

    private function notifyHcFinalizedToAllDivisions(ResignRequest $resign): void
    {
        $employeeName = $resign->employee->nama ?? 'karyawan';
        $recipients = $this->getChecklistDivisionRecipients();
        foreach ($recipients as $recipient) {
            Notification::send(
                $recipient->id,
                'hc_finalized',
                'HC Finalisasi Resign',
                "HC telah memfinalisasi pengajuan resign {$employeeName}. Proses resign selesai.",
                ['resign_id' => $resign->id, 'employee_name' => $employeeName]
            );
        }
    }

    private function getChecklistDivisionRecipients()
    {
        // Kirim ke semua user yang punya akses checklist (semua anggota divisi HC, IT, Doc, Finance, GA),
        // bukan hanya admin. canAccessChecklist() sudah membatasi ke divisi-divisi tersebut.
        return User::all()->filter(function (User $u) {
            return $u->canAccessChecklist();
        });
    }
}
