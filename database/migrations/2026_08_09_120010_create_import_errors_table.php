<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_errors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('import_job_id');
            $table->unsignedBigInteger('row_number');
            $table->json('row_data')->nullable();
            $table->text('error');
            $table->timestamps();

            $table->index('import_job_id');
            $table->foreign('import_job_id')
                  ->references('id')
                  ->on('import_jobs')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_errors');
    }
};
