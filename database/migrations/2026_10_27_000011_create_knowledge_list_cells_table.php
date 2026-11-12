<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_list_cells', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account of the vault')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault of the list')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(KnowledgeListItem::class)->comment('item the cell belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(KnowledgeListColumn::class)->comment('column the cell fills')->constrained()->cascadeOnDelete();
            $table->text('value')->comment('text of the cell, in Markdown');
            $table->timestamps();

            $table->unique(['knowledge_list_item_id', 'knowledge_list_column_id'], 'knowledge_list_cells_unique');
        });
    }
};
