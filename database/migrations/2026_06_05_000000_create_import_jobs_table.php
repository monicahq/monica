<?php

use App\Models\Account;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('file_path');
            $table->string('file_hash')->index();
            $table->integer('total_rows');
            $table->integer('processed_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->string('status')->default('pending')->index();
            $table->longText('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};
