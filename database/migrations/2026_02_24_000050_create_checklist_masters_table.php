<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('checklist_masters')) {
            Schema::create('checklist_masters', function (Blueprint $table) {
                $table->id();
                $table->string('department', 30);
                $table->unsignedBigInteger('admin_user_id')->nullable();
                $table->string('item_key', 100);
                $table->string('item_label');
                $table->string('default_pic')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                $table->index('department', 'idx_checklist_master_department');
                $table->index('admin_user_id', 'idx_checklist_master_admin');
                $table->unique(['department', 'admin_user_id', 'item_key'], 'uq_checklist_master_dept_admin_item');
            });
        }

        $templates = [
            'hc' => [
                'surat_ket_kerja' => 'Surat keterangan kerja/magang',
                'nonaktif_idcard' => 'Non aktif ID card',
                'bpjs' => 'Terkait BPJS',
            ],
            'it' => [
                'kembali_laptop' => 'Mengembalikan laptop',
                'nonaktif_akun' => 'Nonaktif akun',
                'nonaktif_software' => 'Nonaktif software',
            ],
            'doc' => [
                'kembali_buku' => 'Mengembalikan buku (jika ada)',
                'arsip_hardcopy' => 'Arsip hardcopy ke klien & Doc Center',
                'arsip_onedrive' => 'Arsip One Drive',
            ],
            'finance' => [
                'cek_advance' => 'Cek advance',
                'cek_pinjaman' => 'Cek pinjaman',
                'tabungan_kurban' => 'Tabungan kurban',
            ],
            'ga' => [
                'nonaktif_gocorps' => 'Non Aktif Akun GoCorps',
            ],
        ];

        foreach ($templates as $dept => $items) {
            $adminId = DB::table('users')
                ->where('role', $dept)
                ->when(Schema::hasColumn('users', 'checklist_admin'), function ($q) {
                    $q->where('checklist_admin', 1);
                })
                ->orderBy('id')
                ->value('id');

            if (!$adminId) {
                continue;
            }

            $adminName = (string) DB::table('users')->where('id', $adminId)->value('nama');
            foreach ($items as $itemKey => $itemLabel) {
                DB::table('checklist_masters')->updateOrInsert(
                    [
                        'department' => $dept,
                        'admin_user_id' => $adminId,
                        'item_key' => $itemKey,
                    ],
                    [
                        'item_label' => $itemLabel,
                        'default_pic' => $adminName !== '' ? $adminName : null,
                        'is_active' => 1,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_masters');
    }
};
