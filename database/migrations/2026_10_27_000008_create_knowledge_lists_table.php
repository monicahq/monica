<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\KnowledgeFolder;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_lists', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses and by the API');
            $table->foreignIdFor(Account::class)->comment('account of the vault')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault the list belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(KnowledgeFolder::class)->comment('folder the list sits in')->constrained()->cascadeOnDelete();
            $table->string('title')->comment('title of the list');
            $table->text('description')->nullable()->comment('what the list is for, in Markdown');
            $table->timestamps();

            $table->index('vault_id');
        });
    }
};
