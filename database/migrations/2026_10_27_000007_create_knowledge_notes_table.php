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
        Schema::create('knowledge_notes', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses and by the API');
            $table->foreignIdFor(Account::class)->comment('account of the vault')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault the note belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(KnowledgeFolder::class)->comment('folder the note sits in')->constrained()->cascadeOnDelete();
            $table->string('title')->comment('title of the note');
            $table->longText('body')->nullable()->comment('text of the note, in Markdown');
            $table->timestamps();

            $table->index('vault_id');
        });
    }
};
