<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('resign_request')) {
            return;
        }

        Schema::table('resign_request', function (Blueprint $table) {
            if (!Schema::hasColumn('resign_request', 'done_at')) {
                $table->timestamp('done_at')->nullable()->after('workflow_stage');
            }
            if (!Schema::hasColumn('resign_request', 'done_by')) {
                $table->unsignedBigInteger('done_by')->nullable()->after('done_at');
            }
        });
    }

    public function down(): void
    {
        // Non-destructive
    }
};

