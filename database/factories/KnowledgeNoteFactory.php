<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<KnowledgeNote> */
class KnowledgeNoteFactory extends Factory
{
    protected $model = KnowledgeNote::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'knowledge_folder_id' => KnowledgeFolder::factory(),
            'account_id' => fn (array $attributes): int => KnowledgeFolder::query()->findOrFail($attributes['knowledge_folder_id'])->account_id,
            'vault_id' => fn (array $attributes): int => KnowledgeFolder::query()->findOrFail($attributes['knowledge_folder_id'])->vault_id,
            'title' => fake()->randomElement(['Dundies speech', 'Warehouse safety', 'Office party plan']),
            'body' => null,
        ];
    }
}
