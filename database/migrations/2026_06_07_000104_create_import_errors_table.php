<?php

use App\Models\ImportJob;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ImportJob::class)->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('row_data')->nullable();
            $table->text('error_message');
            $table->timestamps();

            $table->index('import_job_id');
            $table->index('row_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_errors');
    }
};
