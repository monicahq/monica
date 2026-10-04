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
        Schema::create('vault_slug_reservations', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account the reservation is scoped to, since a vault slug is unique within one')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault that carried the slug, kept so a stale address can be told from an unused one')->constrained()->cascadeOnDelete();
            $table->string('slug', 128)->comment('slug this vault has carried, never given to another vault of the account even after a rename');
            $table->timestamps();

            $table->unique(['account_id', 'slug']);
        });
    }
};
