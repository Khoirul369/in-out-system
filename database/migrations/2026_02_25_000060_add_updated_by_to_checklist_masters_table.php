<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('checklist_masters')) {
            return;
        }

        if (!Schema::hasColumn('checklist_masters', 'updated_by')) {
            Schema::table('checklist_masters', function (Blueprint $table) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('default_pic');
                $table->index('updated_by', 'idx_checklist_master_updated_by');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('checklist_masters')) {
            return;
        }

        if (Schema::hasColumn('checklist_masters', 'updated_by')) {
            Schema::table('checklist_masters', function (Blueprint $table) {
                $table->dropIndex('idx_checklist_master_updated_by');
                $table->dropColumn('updated_by');
            });
        }
    }
};
