<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\KnowledgeListCell;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<KnowledgeListCell> */
class KnowledgeListCellFactory extends Factory
{
    protected $model = KnowledgeListCell::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'knowledge_list_item_id' => KnowledgeListItem::factory(),
            'knowledge_list_column_id' => fn (array $attributes): int => KnowledgeListColumn::factory()->create([
                'knowledge_list_id' => KnowledgeListItem::query()->findOrFail($attributes['knowledge_list_item_id'])->knowledge_list_id,
            ])->id,
            'account_id' => fn (array $attributes): int => KnowledgeListItem::query()->findOrFail($attributes['knowledge_list_item_id'])->account_id,
            'vault_id' => fn (array $attributes): int => KnowledgeListItem::query()->findOrFail($attributes['knowledge_list_item_id'])->vault_id,
            'value' => fake()->randomElement(['Cooper\'s Seafood', 'Scranton', 'Poor Richard\'s']),
        ];
    }
}
