<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\KnowledgeFolder;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<KnowledgeFolder> */
class KnowledgeFolderFactory extends Factory
{
    protected $model = KnowledgeFolder::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'account_id' => fn (array $attributes): int => Vault::query()->findOrFail($attributes['vault_id'])->account_id,
            'name' => fake()->randomElement(['Home', 'Travel', 'Paper company']),
            'position' => fn (array $attributes): int => KnowledgeFolder::query()->where('vault_id', $attributes['vault_id'])->count() + 1,
        ];
    }
}
