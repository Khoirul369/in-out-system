<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'checklist_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('checklist_admin')->default(false)->after('is_pm');
            });
        }

        $adminRoles = ['hc', 'it', 'doc', 'finance', 'ga'];
        foreach ($adminRoles as $role) {
            $designatedId = DB::table('users')
                ->where('role', $role)
                ->orderBy('id')
                ->value('id');

            if (!$designatedId) {
                continue;
            }

            DB::table('users')
                ->where('role', $role)
                ->update(['checklist_admin' => 0]);

            DB::table('users')
                ->where('id', $designatedId)
                ->update(['checklist_admin' => 1]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'checklist_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('checklist_admin');
            });
        }
    }
};
