<?php

declare(strict_types=1);

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
        Schema::create('vaults', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs and by the API');
            $table->unsignedBigInteger('account_id')->comment('account the vault belongs to');
            $table->string('name')->comment('name the user gave the vault');
            $table->text('description')->nullable()->comment('what the vault is for, shown on the vault list');
            $table->string('colour', 7)->comment('hex colour used to tell vaults apart at a glance');
            $table->unsignedInteger('position')->comment('order the vault appears in, so the user decides which comes first');
            $table->boolean('is_default')->default(false)->comment('whether new records land here when no vault is chosen');
            $table->unsignedInteger('contact_count')->default(0)->comment('running count of contacts, so the list does not aggregate on every render');
            $table->timestamp('last_visited_at')->nullable()->comment('when the user last opened it, used to order the switcher');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });
    }
};
