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
        Schema::create('knowledge_folders', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses and by the API');
            $table->foreignIdFor(Account::class)->comment('account of the vault')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault the folder belongs to')->constrained()->cascadeOnDelete();
            $table->string('name')->comment('name of the folder');
            $table->unsignedInteger('position')->comment('order of the folder in the vault, 1 upward');
            $table->timestamps();

            $table->index('vault_id');
        });
    }
};
