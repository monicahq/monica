<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_slug_reservations', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('vault_id')->comment('vault the reservation is scoped to, since a contact slug is unique within one');
            $table->unsignedBigInteger('contact_id')->comment('contact that carried the slug, kept so a stale address can be told from an unused one');
            $table->string('slug', 128)->comment('slug this contact has carried, never given to another contact of the vault even after a rename');
            $table->timestamps();

            $table->unique(['vault_id', 'slug']);

            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->foreign('contact_id')->references('id')->on('contacts')->cascadeOnDelete();
        });
    }
};
