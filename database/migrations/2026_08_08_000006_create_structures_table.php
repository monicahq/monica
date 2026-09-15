<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structures', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used by the API');
            $table->unsignedBigInteger('account_id')->comment('account the structure belongs to');
            $table->string('owner_type')->comment('class of the thing that owns it, a template or an activity type');
            $table->unsignedBigInteger('owner_id')->comment('id of that owner, carrying no foreign key because the column is a morph');
            $table->string('applies_to', 32)->comment('what kind of record it describes, a contact, an entity or an activity');
            $table->unsignedBigInteger('entity_type_id')->nullable()->comment('type it applies to when applies_to is entity, null otherwise');
            $table->unsignedBigInteger('published_version_id')->nullable()->comment('the version a new record is given, null while only drafts exist');
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id'], 'structures_owner_unique');

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('entity_type_id')->references('id')->on('entity_types')->nullOnDelete();
        });
    }
};
