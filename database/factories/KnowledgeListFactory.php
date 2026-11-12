<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<KnowledgeList> */
class KnowledgeListFactory extends Factory
{
    protected $model = KnowledgeList::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'knowledge_folder_id' => KnowledgeFolder::factory(),
            'account_id' => fn (array $attributes): int => KnowledgeFolder::query()->findOrFail($attributes['knowledge_folder_id'])->account_id,
            'vault_id' => fn (array $attributes): int => KnowledgeFolder::query()->findOrFail($attributes['knowledge_folder_id'])->vault_id,
            'title' => fake()->randomElement(['Favourite restaurants', 'Paper clients', 'Books I have read']),
            'description' => null,
        ];
    }
}
