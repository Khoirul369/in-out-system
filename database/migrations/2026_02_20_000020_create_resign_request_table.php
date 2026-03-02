<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('resign_request')) {
            return;
        }

        Schema::create('resign_request', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employees_id');
            $table->string('resign_file_method')->nullable();
            $table->text('reason')->nullable();
            $table->date('last_date')->nullable();
            $table->text('description')->nullable();
            $table->string('resign_file_path')->nullable();
            $table->string('resign_filename')->nullable();

            $table->string('status')->default('pending');
            $table->string('workflow_stage')->default('to_pm');

            $table->text('approved_description')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->text('approved_hc_description')->nullable();
            $table->timestamp('approved_hc_at')->nullable();
            $table->unsignedBigInteger('approved_hc_by')->nullable();

            $table->timestamp('rejected_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();

            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index('employees_id', 'idx_resign_employees_id');
            $table->index('status', 'idx_resign_status');
            $table->index('workflow_stage', 'idx_resign_workflow_stage');
            $table->index(['employees_id', 'workflow_stage'], 'idx_resign_employee_stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resign_request');
    }
};

