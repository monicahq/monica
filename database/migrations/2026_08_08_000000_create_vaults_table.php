<?php

declare(strict_types=1);

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaults', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs and by the API, and the one that never changes');
            $table->string('slug', 128)->comment('readable identifier used in URLs and by the API, built from the name, unique within the account');
            $table->foreignIdFor(Account::class)->comment('account the vault belongs to')->constrained()->cascadeOnDelete();
            $table->string('name')->comment('name the user gave the vault');
            $table->text('description')->nullable()->comment('what the vault is for, shown on the vault list');
            $table->string('colour', 7)->comment('hex colour used to tell vaults apart at a glance');
            $table->unsignedInteger('position')->comment('order the vault appears in, so the user decides which comes first');
            $table->boolean('is_default')->default(false)->comment('whether new records land here when no vault is chosen');
            $table->unsignedInteger('contact_count')->default(0)->comment('running count of contacts, so the list does not aggregate on every render');
            $table->timestamp('last_visited_at')->nullable()->comment('when the user last opened it, used to order the switcher');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'position']);
            $table->unique(['account_id', 'slug']);
        });
    }
};
