<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('username')->nullable();
                $table->string('password_hash')->nullable();
                $table->string('role')->default('karyawan');
                $table->string('nama')->nullable();
                $table->string('id_karyawan')->nullable();
                $table->string('divisi_posisi')->nullable();
                $table->unsignedBigInteger('pm_id')->nullable();
                $table->boolean('is_pm')->default(false);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });

            return;
        }

        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->after('id');
            });
        }

        if (!Schema::hasColumn('users', 'password_hash')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password_hash')->nullable()->after('password');
            });
        }

        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('karyawan')->after('password_hash');
            });
        }

        if (!Schema::hasColumn('users', 'nama')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('nama')->nullable()->after('role');
            });
        }

        if (!Schema::hasColumn('users', 'id_karyawan')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('id_karyawan')->nullable()->after('nama');
            });
        }

        if (!Schema::hasColumn('users', 'divisi_posisi')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('divisi_posisi')->nullable()->after('id_karyawan');
            });
        }

        if (!Schema::hasColumn('users', 'pm_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('pm_id')->nullable()->after('divisi_posisi');
            });
        }

        if (!Schema::hasColumn('users', 'is_pm')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_pm')->default(false)->after('pm_id');
            });
        }

        if (!Schema::hasColumn('users', 'created_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally left blank to avoid destructive schema rollback
        // on converted/legacy databases.
    }
};

