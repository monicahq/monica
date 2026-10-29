<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('user_id');
            $table->uuid('vault_id')->nullable();
            $table->string('filename');
            $table->string('file_path');
            $table->string('file_hash', 64)->nullable();
            $table->boolean('has_header')->default(false);
            $table->json('header')->nullable();
            $table->unsignedBigInteger('total_rows')->default(0);
            $table->unsignedBigInteger('processed_rows')->default(0);
            $table->unsignedBigInteger('failed_rows')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->text('failure_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index('user_id');
            $table->index('vault_id');
            $table->index('status');
            $table->index('file_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};
