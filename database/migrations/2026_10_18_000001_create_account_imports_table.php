<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_imports', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('public identifier of the import, used by the API and as the source detail of every log it writes');
            $table->foreignIdFor(Account::class)->comment('account the data goes into')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->comment('user who started it')->constrained()->cascadeOnDelete();
            $table->string('source_format', 32)->nullable()->comment('what the file is, monica_4 or monica, null while it is read and when it was refused as neither');
            $table->string('source_checksum', 64)->comment('sha256 of the file, to say it was imported here before');
            $table->string('disk', 64)->comment('filesystem disk holding the uploaded file');
            $table->string('path', 512)->comment('where the uploaded file is on that disk, under the account prefix');
            $table->string('status', 32)->comment('where it stands: reading, refused, read, writing, rolling_back, rolled_back, rollback_failed or completed');
            $table->text('refusal')->nullable()->comment('why the file was refused, null otherwise');
            $table->foreignIdFor(Vault::class)->nullable()->comment('vault chosen for a file of one vault, null until confirmed, for a file of several, and once that vault is deleted')->constrained()->nullOnDelete();
            $table->json('created_vault_ids')->comment('vaults the import created, one per vault of a file of several, destroyed by a rollback');
            $table->string('me_source_uuid', 64)->nullable()->comment('source uuid of the contact chosen as Me, null for none');
            $table->json('summary')->nullable()->comment('per kind, how many were found and how many will not come over, null until the file is read');
            $table->json('source_references')->nullable()->comment('what the file says its own identifiers mean (genders, field kinds, natures, activity types), read once and used while writing');
            $table->uuid('writing_run')->nullable()->comment('identifier of the one write run allowed to go on, so a copy of a run the queue started again stops, null before writing');
            $table->unsignedInteger('items_total')->default(0)->comment('items to write');
            $table->unsignedInteger('items_done')->default(0)->comment('items written, for the progress shown');
            $table->timestamp('ended_at')->nullable()->comment('when it completed or finished rolling back, null while it runs');
            $table->timestamps();

            $table->index(['account_id', 'status']);
            $table->index(['account_id', 'source_checksum']);
        });
    }
};
