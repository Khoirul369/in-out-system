<?php

namespace Tests\Feature;

use App\Models\ResignRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChecklistAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_hc_user_cannot_checklist_own_resign_request(): void
    {
        $hcUserId = DB::table('users')->insertGetId([
            'username' => 'hc_self_'.uniqid(),
            'password_hash' => 'x',
            'role' => 'hc',
            'nama' => 'HC Self',
            'id_karyawan' => 'HC-SELF-001',
            'divisi_posisi' => 'Human Capital',
            'pm_id' => null,
            'is_pm' => 0,
            'created_at' => now(),
        ]);

        $resignId = DB::table('resign_request')->insertGetId([
            'employees_id' => $hcUserId,
            'resign_file_method' => 'upload',
            'reason' => 'Testing resign request for own-checklist authorization.',
            'last_date' => now()->addDays(30)->toDateString(),
            'description' => 'Test data',
            'resign_file_path' => 'uploads/test.pdf',
            'resign_filename' => 'test.pdf',
            'status' => ResignRequest::STATUS_APPROVED_HC,
            'workflow_stage' => ResignRequest::STAGE_TO_HC,
            'created_at' => now(),
            'created_by' => $hcUserId,
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
                        'keterangan' => 'Checklist selesai',
                    ],
                ],
            ]);

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Anda tidak dapat memproses checklist pengajuan resign milik sendiri.',
            ]);

        $this->assertDatabaseHas('resign_checklist_items', [
            'resign_request_id' => $resignId,
            'department' => 'hc',
            'item_key' => 'bpjs',
            'done' => 0,
        ]);
    }

    public function test_hc_user_can_checklist_other_users_resign_request(): void
    {
        $hcUserId = DB::table('users')->insertGetId([
            'username' => 'hc_admin_'.uniqid(),
            'password_hash' => 'x',
            'role' => 'hc',
            'nama' => 'HC Admin',
            'id_karyawan' => 'HC-ADM-001',
            'divisi_posisi' => 'Human Capital',
            'pm_id' => null,
            'is_pm' => 0,
            'created_at' => now(),
        ]);

        $employeeId = DB::table('users')->insertGetId([
            'username' => 'karyawan_a_'.uniqid(),
            'password_hash' => 'x',
            'role' => 'karyawan',
            'nama' => 'Karyawan A',
            'id_karyawan' => 'EMP-001',
            'divisi_posisi' => 'Operasional',
            'pm_id' => null,
            'is_pm' => 0,
            'created_at' => now(),
        ]);

        $resignId = DB::table('resign_request')->insertGetId([
            'employees_id' => $employeeId,
            'resign_file_method' => 'upload',
            'reason' => 'Testing resign request for checklist authorization.',
            'last_date' => now()->addDays(30)->toDateString(),
            'description' => 'Test data',
            'resign_file_path' => 'uploads/test.pdf',
            'resign_filename' => 'test.pdf',
            'status' => ResignRequest::STATUS_APPROVED_HC,
            'workflow_stage' => ResignRequest::STAGE_TO_HC,
            'created_at' => now(),
            'created_by' => $employeeId,
        ]);

        DB::table('resign_checklist_items')->insertOrIgnore([
            [
                'resign_request_id' => $resignId,
                'department' => 'hc',
                'item_key' => 'bpjs',
                'item_label' => 'Terkait BPJS',
                'pic' => 'PIC HC',
                'done' => 0,
                'created_at' => now(),
            ],
            [
                'resign_request_id' => $resignId,
                'department' => 'hc',
                'item_key' => 'nonaktif_idcard',
                'item_label' => 'Non aktif ID card',
                'pic' => null,
                'done' => 0,
                'created_at' => now(),
            ],
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
                        'keterangan' => 'Checklist selesai',
                    ],
                    'nonaktif_idcard' => [
                        'done' => 0,
                        'keterangan' => '',
                    ],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('resign_checklist_items', [
            'resign_request_id' => $resignId,
            'department' => 'hc',
            'item_key' => 'bpjs',
            'done' => 1,
            'pic' => 'PIC HC',
            'keterangan' => 'Checklist selesai',
        ]);
    }

    public function test_hc_user_can_access_checklist_page_even_if_not_designated_admin(): void
    {
        $hcUserId = DB::table('users')->insertGetId([
            'username' => 'hc_non_designated_'.uniqid(),
            'password_hash' => 'x',
            'role' => 'hc',
            'nama' => 'HC Non Designated',
            'id_karyawan' => 'HC-NON-001',
            'divisi_posisi' => 'Human Capital',
            'pm_id' => null,
            'is_pm' => 0,
            'created_at' => now(),
        ]);

        $response = $this
            ->withSession(['user' => ['id' => $hcUserId]])
            ->get(route('checklist.index'));

        $response->assertOk();
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

