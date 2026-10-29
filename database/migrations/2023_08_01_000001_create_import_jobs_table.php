<?php

use App\Models\Account;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('file_path', 500);
            $table->string('original_filename', 255)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->enum('file_type', ['vcard'])->default('vcard');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);

            $table->string('batch_id', 255)->nullable();
            // Stores UUIDs of all contacts created by this import, enabling rollback
            $table->json('contact_ids_created')->nullable();
            // Accumulated error messages for debugging failed imports
            $table->text('error_log')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'status']);
            $table->index(['vault_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('import_jobs');
    }
};
