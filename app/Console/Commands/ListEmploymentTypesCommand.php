<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ListEmploymentTypesCommand extends Command
{
    protected $signature = 'employment:list-types {--filter= : Hanya tampilkan: magang, kontrak, permanen, atau null}';

    protected $description = 'Daftar username dan status employment (magang/kontrak/permanen) dari net_hrd';

    public function handle(): int
    {
        $filter = $this->option('filter');

        $users = User::where('role', 'karyawan')->orderBy('username')->get();
        if ($users->isEmpty()) {
            $this->warn('Tidak ada user dengan role karyawan.');
            return self::FAILURE;
        }

        $rows = [];
        foreach ($users as $user) {
            $type = $this->resolveEmploymentType($user);
            $typeLabel = $type ?? '(tidak terbaca)';

            if ($filter !== null) {
                $want = $filter;
                if ($want === 'null' && $type !== null) {
                    continue;
                }
                if ($want !== 'null' && $type !== $want) {
                    continue;
                }
            }

            $rows[] = [$user->username, $user->nama ?? '-', $user->id_karyawan ?? '-', $typeLabel];
        }

        if (empty($rows)) {
            $this->warn('Tidak ada data yang match filter.');
            return self::SUCCESS;
        }

        $this->table(['Username', 'Nama', 'ID Karyawan', 'Status employment'], $rows);

        if ($filter === null) {
            $magangCount = collect($rows)->where(3, 'magang')->count();
            $kontrakCount = collect($rows)->where(3, 'kontrak')->count();
            $this->newLine();
            $this->info("Ringkasan: Magang = {$magangCount}, Kontrak = {$kontrakCount} (tidak wajib upload surat resign).");
        }

        return self::SUCCESS;
    }

    private function getNetHrdEmployeesId(User $user): ?int
    {
        try {
            if (!Schema::connection('net_hrd')->hasTable('employees') || (empty($user->id_karyawan) && empty($user->username))) {
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
            return null;
        }
    }

    private function resolveEmploymentType(User $user): ?string
    {
        try {
            if (!Schema::connection('net_hrd')->hasTable('employees_status') || !Schema::connection('net_hrd')->hasTable('status')) {
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
