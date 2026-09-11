<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_duplicate_candidates', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('account_id')->comment('account the pair belongs to');
            $table->unsignedBigInteger('vault_id')->comment('vault both contacts live in');
            $table->unsignedBigInteger('contact_id')->comment('lower of the two ids, so one pair is one row whichever side it was found from');
            $table->unsignedBigInteger('other_contact_id')->comment('higher of the two ids');
            $table->unsignedTinyInteger('score')->comment('how strong the resemblance is, 0 to 100');
            $table->json('reasons')->comment('one entry per signal that fired, each naming its kind and, for an identifying field, the field key and the shared value');
            $table->timestamp('dismissed_at')->nullable()->comment('when somebody said these are two different people, null while the pair stands');
            $table->unsignedBigInteger('dismissed_by_user_id')->nullable()->comment('user who said so');
            $table->timestamp('computed_at')->comment('when detection last looked at this pair');
            $table->timestamps();

            $table->unique(['contact_id', 'other_contact_id'], 'contact_duplicate_candidates_pair_unique');
            $table->index(['vault_id', 'dismissed_at', 'score'], 'contact_duplicate_candidates_screen_index');

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->foreign('contact_id')->references('id')->on('contacts')->cascadeOnDelete();
            $table->foreign('other_contact_id')->references('id')->on('contacts')->cascadeOnDelete();
            $table->foreign('dismissed_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
