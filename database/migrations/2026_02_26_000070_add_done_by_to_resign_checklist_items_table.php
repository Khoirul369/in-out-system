<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('resign_checklist_items')) {
            return;
        }
        if (Schema::hasColumn('resign_checklist_items', 'done_by')) {
            return;
        }

        Schema::table('resign_checklist_items', function (Blueprint $table) {
            $table->unsignedBigInteger('done_by')->nullable()->after('done_at');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('resign_checklist_items', 'done_by')) {
            Schema::table('resign_checklist_items', function (Blueprint $table) {
                $table->dropColumn('done_by');
            });
        }
    }
};
