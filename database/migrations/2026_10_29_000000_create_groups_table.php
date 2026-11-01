<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs, by the API and as the UID of the group card');
            $table->foreignIdFor(Account::class)->comment('account the group belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault it lives in')->constrained()->cascadeOnDelete();
            $table->string('slug', 128)->comment('readable address built from the name, unique in the vault; the uuid answers too');
            $table->string('name')->comment('what the group is called, such as the Halpert household');
            $table->text('description')->nullable()->comment('anything the user wants to say about it');
            $table->date('started_on')->nullable()->comment('when the group came together, null when unknown');
            $table->string('started_precision', 16)->default('unknown')->comment('how precisely the start is known, a DatePrecisionEnum value');
            $table->date('ended_on')->nullable()->comment('when the group ended, null while it exists');
            $table->string('ended_precision', 16)->default('unknown')->comment('how precisely the end is known, a DatePrecisionEnum value');
            $table->string('dav_uri')->nullable()->comment('the .vcf name a device chose when it created the card, null for a group made here, whose card is its uuid');
            $table->timestamps();
            $table->softDeletes();
            $table->boolean('deleted_with_vault')->default(false)->comment('whether the group was taken out by the deletion of its vault, so restoring the vault knows what to bring back');

            $table->index(['vault_id', 'deleted_at']);
            $table->unique(['vault_id', 'dav_uri']);
            $table->unique(['vault_id', 'slug']);
        });
    }
};
