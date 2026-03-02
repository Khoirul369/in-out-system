<?php

namespace Tests\Feature;

use App\Models\ResignChecklistItem;
use App\Models\ResignRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_hc_cannot_verify_own_resign_request(): void
    {
        $hcUserId = $this->createUser([
            'username' => 'hc_self_verify_'.uniqid(),
            'role' => 'hc',
            'nama' => 'HC Self Verify',
            'divisi_posisi' => 'Human Capital',
        ]);

        $resignId = $this->createResignRequest([
            'employees_id' => $hcUserId,
            'workflow_stage' => ResignRequest::STAGE_TO_HC_APPROVAL,
            'status' => ResignRequest::STATUS_APPROVED,
            'created_by' => $hcUserId,
        ]);

        $response = $this
            ->withSession(['user' => ['id' => $hcUserId]])
            ->post(route('approval.hc.action'), [
                'id' => $resignId,
                'action' => 'approved',
                'keterangan' => 'Validasi HC',
            ]);

        $response
            ->assertRedirect(route('approval.hc'))
            ->assertSessionHas('error', 'Tidak bisa verifikasi pengajuan sendiri.');

        $this->assertDatabaseHas('resign_request', [
            'id' => $resignId,
            'workflow_stage' => ResignRequest::STAGE_TO_HC_APPROVAL,
        ]);
    }

    public function test_pm_can_only_access_subordinate_resign_detail(): void
    {
        $pmId = $this->createUser([
            'username' => 'pm_user_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'PM User',
            'is_pm' => 1,
            'divisi_posisi' => 'Operasional',
        ]);

        $subordinateId = $this->createUser([
            'username' => 'subordinate_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Subordinate',
            'pm_id' => $pmId,
            'divisi_posisi' => 'Operasional',
        ]);

        $outsiderId = $this->createUser([
            'username' => 'outsider_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Outsider',
            'divisi_posisi' => 'Operasional',
        ]);

        $subordinateResignId = $this->createResignRequest([
            'employees_id' => $subordinateId,
            'workflow_stage' => ResignRequest::STAGE_TO_PM,
            'status' => ResignRequest::STATUS_PENDING,
            'created_by' => $subordinateId,
        ]);

        $outsiderResignId = $this->createResignRequest([
            'employees_id' => $outsiderId,
            'workflow_stage' => ResignRequest::STAGE_TO_PM,
            'status' => ResignRequest::STATUS_PENDING,
            'created_by' => $outsiderId,
        ]);

        $this
            ->withSession(['user' => ['id' => $pmId]])
            ->get(route('resign.detail', $subordinateResignId))
            ->assertOk();

        $this
            ->withSession(['user' => ['id' => $pmId]])
            ->get(route('resign.detail', $outsiderResignId))
            ->assertForbidden();
    }

    public function test_checklist_requires_keterangan_when_item_checked(): void
    {
        $hcUserId = $this->createUser([
            'username' => 'hc_admin_'.uniqid(),
            'role' => 'hc',
            'nama' => 'HC Admin',
            'divisi_posisi' => 'Human Capital',
        ]);

        $employeeId = $this->createUser([
            'username' => 'employee_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Employee',
            'divisi_posisi' => 'Operasional',
        ]);

        $resignId = $this->createResignRequest([
            'employees_id' => $employeeId,
            'workflow_stage' => ResignRequest::STAGE_TO_HC,
            'status' => ResignRequest::STATUS_APPROVED_HC,
            'created_by' => $employeeId,
        ]);

        DB::table('resign_checklist_items')->insertOrIgnore([
            'resign_request_id' => $resignId,
            'department' => 'hc',
            'item_key' => 'bpjs',
            'item_label' => 'Terkait BPJS',
            'done' => 0,
            'created_at' => now(),
        ]);

        $this->markChecklistAdmin($hcUserId);

        $response = $this
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withSession(['user' => ['id' => $hcUserId]])
            ->post(route('checklist.update'), [
                'resign_request_id' => $resignId,
                'items' => [
                    'bpjs' => [
                        'done' => 1,
                        'keterangan' => '',
                    ],
                ],
            ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Checklist wajib diisi Keterangan sebelum disimpan maupun dicentang.',
            ]);

        $this->assertDatabaseHas('resign_checklist_items', [
            'resign_request_id' => $resignId,
            'department' => 'hc',
            'item_key' => 'bpjs',
            'done' => 0,
        ]);
    }

    public function test_doc_checklist_requires_only_keterangan_when_item_checked(): void
    {
        $docUserId = $this->createUser([
            'username' => 'doc_admin_'.uniqid(),
            'role' => 'doc',
            'nama' => 'Doc Admin',
            'divisi_posisi' => 'Document Center',
        ]);

        $employeeId = $this->createUser([
            'username' => 'employee_doc_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Employee Doc',
            'divisi_posisi' => 'Operasional',
        ]);

        $resignId = $this->createResignRequest([
            'employees_id' => $employeeId,
            'workflow_stage' => ResignRequest::STAGE_TO_HC,
            'status' => ResignRequest::STATUS_APPROVED_HC,
            'created_by' => $employeeId,
        ]);

        DB::table('resign_checklist_items')->insertOrIgnore([
            'resign_request_id' => $resignId,
            'department' => 'doc',
            'item_key' => 'arsip_hardcopy',
            'item_label' => 'Arsip hardcopy ke klien & Doc Center',
            'done' => 0,
            'created_at' => now(),
        ]);

        $this->markChecklistAdmin($docUserId);

        $response = $this
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withSession(['user' => ['id' => $docUserId]])
            ->post(route('checklist.update'), [
                'resign_request_id' => $resignId,
                'items' => [
                    'arsip_hardcopy' => [
                        'done' => 1,
                        'keterangan' => 'Dokumen sudah diarsipkan.',
                    ],
                ],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('resign_checklist_items', [
            'resign_request_id' => $resignId,
            'department' => 'doc',
            'item_key' => 'arsip_hardcopy',
            'done' => 1,
            'pic' => null,
            'keterangan' => 'Dokumen sudah diarsipkan.',
        ]);
    }

    public function test_readonly_hc_user_cannot_verify_or_checklist_but_can_view_resign_list(): void
    {
        $readonlyHcId = 32;
        $existingUser = DB::table('users')->where('id', $readonlyHcId)->exists();
        if ($existingUser) {
            DB::table('users')->where('id', $readonlyHcId)->update([
                'username' => 'erry.riyadi@muc.co.id',
                'role' => 'hc',
                'nama' => 'Erry Riyadi',
                'divisi_posisi' => 'Human Capital',
            ]);
        } else {
            $this->createUser([
                'id' => $readonlyHcId,
                'username' => 'erry.riyadi@muc.co.id',
                'role' => 'hc',
                'nama' => 'Erry Riyadi',
                'divisi_posisi' => 'Human Capital',
            ]);
        }

        if (Schema::hasColumn('users', 'checklist_admin')) {
            DB::table('users')->where('id', $readonlyHcId)->update(['checklist_admin' => 1]);
        }

        $this
            ->withSession(['user' => ['id' => $readonlyHcId]])
            ->get(route('approval.hc'))
            ->assertForbidden();

        $this
            ->withSession(['user' => ['id' => $readonlyHcId]])
            ->get(route('checklist.index'))
            ->assertForbidden();

        $this
            ->withSession(['user' => ['id' => $readonlyHcId]])
            ->get(route('resign.list'))
            ->assertOk();
    }

    public function test_checklist_generation_uses_active_master_items_by_department(): void
    {
        if (!Schema::hasTable('checklist_masters')) {
            $this->markTestSkipped('Tabel checklist_masters belum tersedia. Jalankan migration terbaru.');
        }

        $hcUserId = $this->createUser([
            'username' => 'hc_master_'.uniqid(),
            'role' => 'hc',
            'nama' => 'HC Master',
            'divisi_posisi' => 'Human Capital',
        ]);

        $employeeId = $this->createUser([
            'username' => 'employee_master_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Employee Master',
            'divisi_posisi' => 'Operasional',
        ]);

        $resignId = $this->createResignRequest([
            'employees_id' => $employeeId,
            'workflow_stage' => ResignRequest::STAGE_TO_HC,
            'status' => ResignRequest::STATUS_APPROVED_HC,
            'created_by' => $employeeId,
        ]);

        DB::table('checklist_masters')->insert([
            'department' => 'hc',
            'admin_user_id' => $hcUserId,
            'item_key' => 'custom_hc_test_item',
            'item_label' => 'Custom HC Test Item',
            'default_pic' => 'PIC HC Custom',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ResignChecklistItem::createDefaultItems($resignId);

        $this->assertDatabaseHas('resign_checklist_items', [
            'resign_request_id' => $resignId,
            'department' => 'hc',
            'item_key' => 'custom_hc_test_item',
            'item_label' => 'Custom HC Test Item',
            'pic' => 'PIC HC Custom',
            'done' => 0,
        ]);
    }

    public function test_karyawan_with_department_keyword_is_not_treated_as_admin_role(): void
    {
        $userId = $this->createUser([
            'username' => 'karyawan_hc_text_'.uniqid(),
            'role' => 'karyawan',
            'nama' => 'Karyawan HC Text',
            'divisi_posisi' => 'Human Capital',
        ]);

        $user = User::findOrFail($userId);

        $this->assertTrue($user->isHc());
        $this->assertFalse($user->isAdmin());
    }

    public function test_resign_request_transition_matrix_blocks_invalid_stage_jump(): void
    {
        $resign = new ResignRequest([
            'workflow_stage' => ResignRequest::STAGE_TO_PM,
        ]);

        $this->assertTrue($resign->canTransitionTo(ResignRequest::STAGE_TO_HC_APPROVAL));
        $this->assertTrue($resign->canTransitionTo(ResignRequest::STAGE_PM_REJECTED));
        $this->assertFalse($resign->canTransitionTo(ResignRequest::STAGE_TO_HC));
        $this->assertFalse($resign->canTransitionTo(ResignRequest::STAGE_COMPLETED));
    }

    public function test_resign_request_terminal_stage_detection(): void
    {
        $resignDone = new ResignRequest([
            'workflow_stage' => ResignRequest::STAGE_COMPLETED,
        ]);
        $resignRejected = new ResignRequest([
            'workflow_stage' => ResignRequest::STAGE_HC_REJECTED,
        ]);
        $resignActive = new ResignRequest([
            'workflow_stage' => ResignRequest::STAGE_TO_HC,
        ]);

        $this->assertTrue($resignDone->isTerminalStage());
        $this->assertTrue($resignRejected->isTerminalStage());
        $this->assertFalse($resignActive->isTerminalStage());
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
            'reason' => 'Testing resign workflow.',
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

    private function markChecklistAdmin(int $userId): void
    {
        if (!Schema::hasColumn('users', 'checklist_admin')) {
            return;
        }

        $role = DB::table('users')->where('id', $userId)->value('role');
        if (!$role) {
            return;
        }

        DB::table('users')->where('role', $role)->update(['checklist_admin' => 0]);
        DB::table('users')->where('id', $userId)->update(['checklist_admin' => 1]);
    }
}

