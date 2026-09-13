<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relationships', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs and by the API');
            $table->unsignedBigInteger('account_id')->comment('account the relationship belongs to');
            $table->unsignedBigInteger('vault_id')->comment('vault both contacts live in');
            $table->unsignedBigInteger('relationship_type_id')->comment('structure of the link');
            $table->unsignedBigInteger('contact_a_id')->comment('side carrying role A of a directed type');
            $table->unsignedBigInteger('contact_b_id')->comment('side carrying role B of a directed type');
            $table->string('status', 32)->comment('whether the relationship applies today, a RelationshipStatusEnum value');
            $table->date('started_on')->nullable()->comment('known start date');
            $table->date('ended_on')->nullable()->comment('known end date');
            $table->text('notes')->nullable()->comment('context written by the user');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vault_id', 'contact_a_id', 'relationship_type_id', 'deleted_at'], 'relationships_side_a_index');
            $table->index(['vault_id', 'contact_b_id', 'relationship_type_id', 'deleted_at'], 'relationships_side_b_index');

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->foreign('relationship_type_id')->references('id')->on('relationship_types')->cascadeOnDelete();
            $table->foreign('contact_a_id')->references('id')->on('contacts')->cascadeOnDelete();
            $table->foreign('contact_b_id')->references('id')->on('contacts')->cascadeOnDelete();
        });
    }
};
