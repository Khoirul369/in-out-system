<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration untuk connection net_hrd.
 * Jalankan: php artisan migrate --database=net_hrd
 *
 * Membuat tabel employees jika belum ada.
 * PENTING: Saat mengisi data (import/sync), sertakan karyawan AKTIF dan TIDAK AKTIF
 * agar ID karyawan tetap konsisten dan tidak bergeser (mencegah mismatch dengan API leave, dll.).
 */
return new class extends Migration
{
    protected $connection = 'net_hrd';

    public function up(): void
    {
        if (Schema::connection('net_hrd')->hasTable('employees')) {
            return;
        }

        Schema::connection('net_hrd')->create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->nullable();
            $table->string('id_karyawan', 50)->nullable()->comment('NIK / ID karyawan');
            $table->string('nik', 50)->nullable();
            $table->string('username')->nullable();
            $table->boolean('is_active')->default(true)->comment('1=aktif, 0=tidak aktif - tetap isi semua karyawan');
            $table->string('divisi_posisi')->nullable();
            $table->unsignedBigInteger('pm_id')->nullable();
            $table->timestamps();

            $table->index('id_karyawan');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('net_hrd')->dropIfExists('employees');
    }
};
