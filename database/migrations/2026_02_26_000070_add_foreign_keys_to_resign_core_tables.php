<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addResignRequestEmployeeFk();
        $this->addChecklistItemResignFk();
        $this->addChecklistMasterUserFks();
    }

    public function down(): void
    {
        $this->dropForeignIfExists('resign_request', 'fk_resign_request_employee');
        $this->dropForeignIfExists('resign_checklist_items', 'fk_checklist_items_resign_request');
        $this->dropForeignIfExists('checklist_masters', 'fk_checklist_master_admin_user');
        $this->dropForeignIfExists('checklist_masters', 'fk_checklist_master_updated_by');
    }

    private function addResignRequestEmployeeFk(): void
    {
        if (!Schema::hasTable('resign_request') || !Schema::hasTable('users')) {
            return;
        }
        if (!Schema::hasColumn('resign_request', 'employees_id')) {
            return;
        }
        if ($this->hasOrphanRows('resign_request', 'employees_id', 'users', 'id')) {
            return;
        }

        $this->addForeignSafely('resign_request', function (Blueprint $table) {
            $table->foreign('employees_id', 'fk_resign_request_employee')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    private function addChecklistItemResignFk(): void
    {
        if (!Schema::hasTable('resign_checklist_items') || !Schema::hasTable('resign_request')) {
            return;
        }
        if (!Schema::hasColumn('resign_checklist_items', 'resign_request_id')) {
            return;
        }
        if ($this->hasOrphanRows('resign_checklist_items', 'resign_request_id', 'resign_request', 'id')) {
            return;
        }

        $this->addForeignSafely('resign_checklist_items', function (Blueprint $table) {
            $table->foreign('resign_request_id', 'fk_checklist_items_resign_request')
                ->references('id')
                ->on('resign_request')
                ->onDelete('cascade');
        });
    }

    private function addChecklistMasterUserFks(): void
    {
        if (!Schema::hasTable('checklist_masters') || !Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('checklist_masters', 'admin_user_id')
            && !$this->hasOrphanRows('checklist_masters', 'admin_user_id', 'users', 'id')) {
            $this->addForeignSafely('checklist_masters', function (Blueprint $table) {
                $table->foreign('admin_user_id', 'fk_checklist_master_admin_user')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            });
        }

        if (Schema::hasColumn('checklist_masters', 'updated_by')
            && !$this->hasOrphanRows('checklist_masters', 'updated_by', 'users', 'id')) {
            $this->addForeignSafely('checklist_masters', function (Blueprint $table) {
                $table->foreign('updated_by', 'fk_checklist_master_updated_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            });
        }
    }

    private function addForeignSafely(string $table, \Closure $callback): void
    {
        try {
            Schema::table($table, $callback);
        } catch (\Throwable $e) {
            // No-op: migration remains safe on legacy/partially constrained schemas.
        }
    }

    private function dropForeignIfExists(string $table, string $foreignName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($foreignName) {
                $table->dropForeign($foreignName);
            });
        } catch (\Throwable $e) {
            // No-op: skip when constraint is absent.
        }
    }

    private function hasOrphanRows(
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn
    ): bool {
        return DB::table("{$table} as src")
            ->leftJoin("{$referencedTable} as ref", "src.{$column}", '=', "ref.{$referencedColumn}")
            ->whereNotNull("src.{$column}")
            ->whereNull("ref.{$referencedColumn}")
            ->exists();
    }
};
