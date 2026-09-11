<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_merges', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier of the merge, used wherever it is addressed from outside');
            $table->unsignedBigInteger('account_id')->comment('account the merge happened in');
            $table->unsignedBigInteger('vault_id')->comment('vault both contacts live in');
            $table->unsignedBigInteger('kept_contact_id')->comment('contact that survived the merge, carrying no foreign key since the record of a merge outlives the contacts it names');
            $table->unsignedBigInteger('merged_contact_id')->comment('contact that was absorbed, soft deleted rather than destroyed, and carrying no foreign key for the same reason');
            $table->json('resolutions')->comment('how each conflict was decided, by field key and repeater row, and which collisions were kept or dropped');
            $table->json('ledger')->comment('what moved, each entry naming the table, the row and the record it came from, so the undo can replay it backwards');
            $table->unsignedBigInteger('snapshot_id')->nullable()->comment('state of the survivor captured before anything was written, null once that snapshot has been pruned');
            $table->unsignedBigInteger('merged_by_user_id')->nullable()->comment('user who ran it, null once that user is deleted and null when an import did');
            $table->timestamp('undone_at')->nullable()->comment('when it was reversed, null while it stands');
            $table->unsignedBigInteger('undone_by_user_id')->nullable()->comment('user who reversed it');
            $table->timestamps();

            $table->index(['merged_contact_id', 'undone_at'], 'contact_merges_absorbed_index');
            $table->index(['vault_id', 'created_at']);
            $table->index('kept_contact_id');

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->foreign('snapshot_id')->references('id')->on('record_snapshots')->nullOnDelete();
            $table->foreign('merged_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('undone_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
