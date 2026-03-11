<?php

namespace Tests\Feature;

use App\Models\ResignRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResignEmploymentStatusValidationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Dalam environment test, pakai koneksi MySQL yang sama untuk simulasi net_hrd.
        Config::set('database.connections.net_hrd', config('database.connections.mysql'));
    }

    public function test_permanent_employee_must_upload_resign_letter(): void
    {
        $this->prepareNetHrdTables();

        $userId = $this->createKaryawanUser();
        $this->seedEmploymentStatus($userId, 'Permanen');

        $response = $this
            ->withSession(['user' => ['id' => $userId]])
            ->post(route('resign.submit'), [
                'alasan' => str_repeat('Alasan resign valid. ', 4), // > 50 chars
                'tanggal_berhenti' => now()->addDays(30)->toDateString(),
                'deskripsi' => 'Tes permanen wajib upload',
            ]);

        $response->assertSessionHasErrors(['resign_file']);

        $this->assertDatabaseMissing('resign_request', [
            'employees_id' => $userId,
            'description' => 'Tes permanen wajib upload',
        ]);
    }

    public function test_contract_employee_can_submit_without_resign_letter(): void
    {
        $this->prepareNetHrdTables();

        $userId = $this->createKaryawanUser();
        $this->seedEmploymentStatus($userId, 'Kontrak');

        $response = $this
            ->withSession(['user' => ['id' => $userId]])
            ->post(route('resign.submit'), [
                'alasan' => str_repeat('Alasan resign valid. ', 4), // > 50 chars
                'tanggal_berhenti' => now()->addDays(30)->toDateString(),
                'deskripsi' => 'Tes kontrak tanpa upload',
            ]);

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Pengajuan resign berhasil dikirim.');

        $this->assertDatabaseHas('resign_request', [
            'employees_id' => $userId,
            'status' => ResignRequest::STATUS_PENDING,
            'resign_file_method' => 'manual',
            'resign_file_path' => null,
            'resign_filename' => null,
        ]);
    }

    private function createKaryawanUser(): int
    {
        return (int) DB::table('users')->insertGetId([
            'username' => 'karyawan_status_'.uniqid(),
            'password_hash' => 'x',
            'role' => 'karyawan',
            'nama' => 'Karyawan Status',
            'id_karyawan' => 'EMP-'.uniqid(),
            'divisi_posisi' => 'Operasional',
            'pm_id' => null,
            'is_pm' => 0,
            'created_at' => now(),
        ]);
    }

    private function prepareNetHrdTables(): void
    {
        if (!Schema::connection('net_hrd')->hasTable('status')) {
            Schema::connection('net_hrd')->create('status', function ($table) {
                $table->bigIncrements('id');
                $table->string('status');
            });
        }

        if (!Schema::connection('net_hrd')->hasTable('employees_status')) {
            Schema::connection('net_hrd')->create('employees_status', function ($table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('employees_id');
                $table->unsignedBigInteger('status_id');
                $table->boolean('is_active')->default(true);
            });
        }

        if (!Schema::connection('net_hrd')->hasColumn('status', 'id')
            || !Schema::connection('net_hrd')->hasColumn('status', 'status')
            || !Schema::connection('net_hrd')->hasColumn('employees_status', 'employees_id')
            || !Schema::connection('net_hrd')->hasColumn('employees_status', 'status_id')) {
            $this->markTestSkipped('Struktur tabel net_hrd tidak sesuai untuk test ini.');
        }
    }

    private function seedEmploymentStatus(int $userId, string $statusName): void
    {
        $statusId = (int) DB::connection('net_hrd')
            ->table('status')
            ->where('status', $statusName)
            ->value('id');

        if ($statusId === 0) {
            $statusId = (int) DB::connection('net_hrd')
                ->table('status')
                ->insertGetId(['status' => $statusName]);
        }

        DB::connection('net_hrd')
            ->table('employees_status')
            ->where('employees_id', $userId)
            ->delete();

        DB::connection('net_hrd')
            ->table('employees_status')
            ->insert([
                'employees_id' => $userId,
                'status_id' => $statusId,
                'is_active' => 1,
            ]);
    }
}
