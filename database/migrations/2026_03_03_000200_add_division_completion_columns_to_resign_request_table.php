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
            foreach (['hc', 'it', 'doc', 'finance', 'ga'] as $dept) {
                $at = "completed_{$dept}_at";
                $by = "completed_{$dept}_by";

                if (!Schema::hasColumn('resign_request', $at)) {
                    $table->timestamp($at)->nullable()->after('workflow_stage');
                }
                if (!Schema::hasColumn('resign_request', $by)) {
                    $table->unsignedBigInteger($by)->nullable()->after($at);
                }
            }
        });
    }

    public function down(): void
    {
        // Non-destructive: skip rollback on legacy schemas
    }
};

