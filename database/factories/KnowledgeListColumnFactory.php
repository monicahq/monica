<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<KnowledgeListColumn> */
class KnowledgeListColumnFactory extends Factory
{
    protected $model = KnowledgeListColumn::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'knowledge_list_id' => KnowledgeList::factory(),
            'account_id' => fn (array $attributes): int => KnowledgeList::query()->findOrFail($attributes['knowledge_list_id'])->account_id,
            'vault_id' => fn (array $attributes): int => KnowledgeList::query()->findOrFail($attributes['knowledge_list_id'])->vault_id,
            'name' => fake()->randomElement(['Name', 'City', 'Note']),
            'position' => fn (array $attributes): int => KnowledgeListColumn::query()->where('knowledge_list_id', $attributes['knowledge_list_id'])->count() + 1,
        ];
    }
}
