<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('resign_request')) {
            return;
        }

        if ($this->hasInvalidStatusOrStageData()) {
            return;
        }

        $this->runStatementSafely(
            "ALTER TABLE resign_request
             ADD CONSTRAINT chk_resign_status_enum
             CHECK (status IN ('pending','approved','approved_hc','rejected','done'))"
        );

        $this->runStatementSafely(
            "ALTER TABLE resign_request
             ADD CONSTRAINT chk_resign_workflow_enum
             CHECK (workflow_stage IN ('to_pm','to_hc_approval','to_hc','completed','pm_rejected','hc_rejected'))"
        );

        $this->runStatementSafely(
            "ALTER TABLE resign_request
             ADD CONSTRAINT chk_resign_status_workflow_consistency
             CHECK (
               (status = 'pending' AND workflow_stage = 'to_pm')
               OR (status = 'pending' AND workflow_stage = 'to_hc_approval')
               OR (status = 'approved' AND workflow_stage = 'to_hc_approval')
               OR (status = 'approved_hc' AND workflow_stage = 'to_hc')
               OR (status = 'done' AND workflow_stage = 'completed')
               OR (status = 'rejected' AND workflow_stage IN ('pm_rejected','hc_rejected'))
             )"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('resign_request')) {
            return;
        }

        $this->dropCheckSafely('resign_request', 'chk_resign_status_workflow_consistency');
        $this->dropCheckSafely('resign_request', 'chk_resign_workflow_enum');
        $this->dropCheckSafely('resign_request', 'chk_resign_status_enum');
    }

    private function hasInvalidStatusOrStageData(): bool
    {
        return DB::table('resign_request')
            ->where(function ($q) {
                $q->whereNotIn('status', ['pending', 'approved', 'approved_hc', 'rejected', 'done'])
                    ->orWhereNotIn('workflow_stage', ['to_pm', 'to_hc_approval', 'to_hc', 'completed', 'pm_rejected', 'hc_rejected'])
                    ->orWhereRaw(
                        "NOT (
                            (status = 'pending' AND workflow_stage = 'to_pm')
                            OR (status = 'pending' AND workflow_stage = 'to_hc_approval')
                            OR (status = 'approved' AND workflow_stage = 'to_hc_approval')
                            OR (status = 'approved_hc' AND workflow_stage = 'to_hc')
                            OR (status = 'done' AND workflow_stage = 'completed')
                            OR (status = 'rejected' AND workflow_stage IN ('pm_rejected','hc_rejected'))
                        )"
                    );
            })
            ->exists();
    }

    private function runStatementSafely(string $sql): void
    {
        try {
            DB::statement($sql);
        } catch (\Throwable $e) {
            // No-op: defensive for engines that do not support check constraints.
        }
    }

    private function dropCheckSafely(string $table, string $constraint): void
    {
        // MySQL/MariaDB variants use different syntaxes.
        $this->runStatementSafely("ALTER TABLE {$table} DROP CHECK {$constraint}");
        $this->runStatementSafely("ALTER TABLE {$table} DROP CONSTRAINT {$constraint}");
    }
};
