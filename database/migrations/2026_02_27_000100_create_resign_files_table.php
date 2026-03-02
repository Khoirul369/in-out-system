<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('resign_files')) {
            return;
        }

        Schema::create('resign_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resign_request_id');
            $table->string('title')->nullable();
            $table->string('filename');
            $table->string('filepath');

            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index('resign_request_id', 'idx_resign_files_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resign_files');
    }
};

