<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\KnowledgeList;
use App\Models\KnowledgeListItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<KnowledgeListItem> */
class KnowledgeListItemFactory extends Factory
{
    protected $model = KnowledgeListItem::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'knowledge_list_id' => KnowledgeList::factory(),
            'account_id' => fn (array $attributes): int => KnowledgeList::query()->findOrFail($attributes['knowledge_list_id'])->account_id,
            'vault_id' => fn (array $attributes): int => KnowledgeList::query()->findOrFail($attributes['knowledge_list_id'])->vault_id,
            'position' => fn (array $attributes): int => KnowledgeListItem::query()->where('knowledge_list_id', $attributes['knowledge_list_id'])->count() + 1,
        ];
    }
}
