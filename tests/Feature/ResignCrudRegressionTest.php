<?php

namespace Tests\Feature;

use App\Models\ResignRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResignCrudRegressionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_karyawan_can_submit_resign_request(): void
    {
        Storage::fake('public');

        $pmId = $this->createUser([
            'username' => 'pm_submit_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'PM Submit',
            'is_pm' => 1,
        ]);
        $employeeId = $this->createUser([
            'username' => 'employee_submit_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Employee Submit',
            'pm_id' => $pmId,
        ]);

        $response = $this
            ->withSession(['user' => ['id' => $employeeId]])
            ->post(route('resign.submit'), [
                'alasan' => str_repeat('Alasan resign valid. ', 4),
                'tanggal_berhenti' => now()->addDays(20)->toDateString(),
                'deskripsi' => 'Pengajuan untuk pengujian otomatis.',
                'resign_file' => UploadedFile::fake()->create('resign-letter.pdf', 100, 'application/pdf'),
            ]);

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success')
            ->assertSessionHas('redirect_after_toast');

        $this->assertDatabaseHas('resign_request', [
            'employees_id' => $employeeId,
            'status' => ResignRequest::STATUS_PENDING,
            'workflow_stage' => ResignRequest::STAGE_TO_PM,
            'created_by' => $employeeId,
        ]);
    }

    public function test_submit_is_blocked_when_active_request_exists(): void
    {
        $employeeId = $this->createUser([
            'username' => 'employee_active_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Employee Active',
        ]);

        $this->createResignRequest([
            'employees_id' => $employeeId,
            'status' => ResignRequest::STATUS_PENDING,
            'workflow_stage' => ResignRequest::STAGE_TO_PM,
            'created_by' => $employeeId,
        ]);

        $response = $this
            ->withSession(['user' => ['id' => $employeeId]])
            ->post(route('resign.submit'), [
                'alasan' => str_repeat('A', 60),
                'tanggal_berhenti' => now()->addDays(15)->toDateString(),
            ]);

        $response
            ->assertRedirect(route('resign.create'))
            ->assertSessionHas('error');
    }

    public function test_owner_can_update_pending_resign_request(): void
    {
        $employeeId = $this->createUser([
            'username' => 'employee_update_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Employee Update',
        ]);

        $resignId = $this->createResignRequest([
            'employees_id' => $employeeId,
            'status' => ResignRequest::STATUS_PENDING,
            'workflow_stage' => ResignRequest::STAGE_TO_PM,
            'created_by' => $employeeId,
            'reason' => str_repeat('Old reason ', 6),
        ]);

        $response = $this
            ->withSession(['user' => ['id' => $employeeId]])
            ->post(route('resign.update', $resignId), [
                'alasan' => str_repeat('Alasan baru sudah cukup panjang. ', 3),
                'tanggal_berhenti' => now()->addDays(25)->toDateString(),
                'deskripsi' => 'Deskripsi yang sudah diperbarui.',
            ]);

        $response
            ->assertRedirect(route('resign.detail', $resignId))
            ->assertSessionHas('success', 'Pengajuan berhasil diperbarui.');

        $this->assertDatabaseHas('resign_request', [
            'id' => $resignId,
            'employees_id' => $employeeId,
            'description' => 'Deskripsi yang sudah diperbarui.',
            'updated_by' => $employeeId,
        ]);
    }

    public function test_non_owner_cannot_edit_resign_request(): void
    {
        $ownerId = $this->createUser([
            'username' => 'employee_owner_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Employee Owner',
        ]);
        $otherUserId = $this->createUser([
            'username' => 'employee_other_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Employee Other',
        ]);

        $resignId = $this->createResignRequest([
            'employees_id' => $ownerId,
            'status' => ResignRequest::STATUS_PENDING,
            'workflow_stage' => ResignRequest::STAGE_TO_PM,
            'created_by' => $ownerId,
        ]);

        $this
            ->withSession(['user' => ['id' => $otherUserId]])
            ->get(route('resign.edit', $resignId))
            ->assertForbidden();
    }

    public function test_owner_can_cancel_pending_resign_request(): void
    {
        $employeeId = $this->createUser([
            'username' => 'employee_cancel_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Employee Cancel',
        ]);

        $resignId = $this->createResignRequest([
            'employees_id' => $employeeId,
            'status' => ResignRequest::STATUS_PENDING,
            'workflow_stage' => ResignRequest::STAGE_TO_PM,
            'created_by' => $employeeId,
        ]);

        $response = $this
            ->withSession(['user' => ['id' => $employeeId]])
            ->post(route('resign.cancel', $resignId));

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Pengajuan resign berhasil dibatalkan.');

        $this->assertDatabaseMissing('resign_request', [
            'id' => $resignId,
        ]);
    }

    public function test_detail_is_accessible_for_owner_only_on_employee_role(): void
    {
        $ownerId = $this->createUser([
            'username' => 'employee_detail_owner_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Employee Detail Owner',
        ]);
        $otherUserId = $this->createUser([
            'username' => 'employee_detail_other_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Employee Detail Other',
        ]);

        $resignId = $this->createResignRequest([
            'employees_id' => $ownerId,
            'status' => ResignRequest::STATUS_PENDING,
            'workflow_stage' => ResignRequest::STAGE_TO_PM,
            'created_by' => $ownerId,
        ]);

        $this
            ->withSession(['user' => ['id' => $ownerId]])
            ->get(route('resign.detail', $resignId))
            ->assertOk();

        $this
            ->withSession(['user' => ['id' => $otherUserId]])
            ->get(route('resign.detail', $resignId))
            ->assertForbidden();
    }

    public function test_pm_list_only_shows_direct_subordinates(): void
    {
        $pmId = $this->createUser([
            'username' => 'pm_list_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'PM List',
            'is_pm' => 1,
        ]);
        $subordinateId = $this->createUser([
            'username' => 'employee_sub_list_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Subordinate List',
            'pm_id' => $pmId,
        ]);
        $outsiderId = $this->createUser([
            'username' => 'employee_out_list_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Outsider List',
        ]);

        $this->createResignRequest([
            'employees_id' => $subordinateId,
            'status' => ResignRequest::STATUS_PENDING,
            'workflow_stage' => ResignRequest::STAGE_TO_PM,
            'created_by' => $subordinateId,
        ]);
        $this->createResignRequest([
            'employees_id' => $outsiderId,
            'status' => ResignRequest::STATUS_PENDING,
            'workflow_stage' => ResignRequest::STAGE_TO_PM,
            'created_by' => $outsiderId,
        ]);

        $response = $this
            ->withSession(['user' => ['id' => $pmId]])
            ->get(route('resign.list'));

        $response
            ->assertOk()
            ->assertSee('Subordinate List')
            ->assertDontSee('Outsider List');
    }

    private function createUser(array $overrides = []): int
    {
        $defaults = [
            'username' => 'user_'.uniqid(),
            'password_hash' => 'x',
            'role' => 'karyawan',
            'nama' => 'Test User',
            'id_karyawan' => 'EMP-'.uniqid(),
            'divisi_posisi' => 'Operasional',
            'pm_id' => null,
            'is_pm' => 0,
            'created_at' => now(),
        ];

        return (int) DB::table('users')->insertGetId(array_merge($defaults, $overrides));
    }

    private function createResignRequest(array $overrides = []): int
    {
        $defaults = [
            'employees_id' => 0,
            'resign_file_method' => 'upload',
            'reason' => str_repeat('Testing reason ', 5),
            'last_date' => now()->addDays(30)->toDateString(),
            'description' => 'Test data',
            'resign_file_path' => 'uploads/test.pdf',
            'resign_filename' => 'test.pdf',
            'status' => ResignRequest::STATUS_PENDING,
            'workflow_stage' => ResignRequest::STAGE_TO_PM,
            'created_at' => now(),
            'created_by' => null,
        ];

        return (int) DB::table('resign_request')->insertGetId(array_merge($defaults, $overrides));
    }
}
