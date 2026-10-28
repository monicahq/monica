<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\AccountImport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_import_items', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account of the import')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(AccountImport::class)->comment('import it was read for')->constrained()->cascadeOnDelete();
            $table->string('vault_source_uuid', 64)->nullable()->comment('vault of the file it belongs to, null for a Monica 4 file');
            $table->string('kind', 32)->comment('what it is: contact, relationship, life_event, file, note, reminder, entity, structure, and so on');
            $table->string('source_uuid', 64)->nullable()->comment('identifier in the file, null when the file gives none');
            $table->string('name', 255)->nullable()->comment('name of a contact as the file shows it, to choose who is the account holder, null for every other kind');
            $table->unsignedInteger('position')->comment('order of writing, dependencies first, and walked backwards by a rollback');
            $table->json('payload')->nullable()->comment('what the file said, cleared once written');
            $table->string('outcome', 32)->nullable()->comment('imported, merged_into_me or left_out, null until written, and never written again once set');
            $table->string('reason', 255)->nullable()->comment('why it was left out, as a translation key, null otherwise');
            $table->string('record_type', 64)->nullable()->comment('morph type of the record it created, null until then');
            $table->unsignedBigInteger('record_id')->nullable()->comment('record it created, carrying no foreign key since it is one half of a morph');
            $table->timestamps();

            $table->index(['account_import_id', 'outcome', 'position']);
            $table->index(['account_import_id', 'position']);
            $table->index(['account_import_id', 'kind', 'source_uuid']);
            $table->index(['account_import_id', 'record_type', 'record_id'], 'account_import_items_record_index');
        });
    }
};
