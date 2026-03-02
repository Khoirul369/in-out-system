<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('resign_request')) {
            return;
        }

        if (!Schema::hasColumn('resign_request', 'rejected_stage')) {
            Schema::table('resign_request', function (Blueprint $table) {
                $table->string('rejected_stage', 10)->nullable()->after('rejected_by');
            });
        }

        // Backfill data lama agar audit trail reject konsisten.
        DB::table('resign_request')
            ->where('status', 'rejected')
            ->whereIn('workflow_stage', ['pm_rejected', 'hc_rejected'])
            ->whereNull('rejected_stage')
            ->update([
                'rejected_stage' => DB::raw("CASE WHEN workflow_stage = 'pm_rejected' THEN 'pm' ELSE 'hc' END"),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('resign_request') || !Schema::hasColumn('resign_request', 'rejected_stage')) {
            return;
        }

        Schema::table('resign_request', function (Blueprint $table) {
            $table->dropColumn('rejected_stage');
        });
    }
};
