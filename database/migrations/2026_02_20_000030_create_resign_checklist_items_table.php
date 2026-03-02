<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('resign_checklist_items')) {
            return;
        }

        Schema::create('resign_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resign_request_id');
            $table->string('department', 30);
            $table->string('item_key', 100);
            $table->string('item_label')->nullable();

            $table->string('pic')->nullable();
            $table->string('pj')->nullable();
            $table->text('keterangan')->nullable();

            $table->boolean('done')->default(false);
            $table->timestamp('done_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('resign_request_id', 'idx_checklist_resign_request_id');
            $table->index('department', 'idx_checklist_department');
            $table->index(['resign_request_id', 'done'], 'idx_checklist_resign_done');
            $table->unique(['resign_request_id', 'department', 'item_key'], 'uq_resign_dept_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resign_checklist_items');
    }
};

