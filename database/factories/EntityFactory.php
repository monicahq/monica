<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Entity;
use App\Models\EntityType;
use App\Models\TemplateVersion;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Entity>
 */
class EntityFactory extends Factory
{
    protected $model = Entity::class;

    /**
     * A record in one account living in the vault of another is nothing, so the
     * account is taken from the vault rather than made beside it.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'account_id' => fn (array $attributes): int => Vault::query()->findOrFail($attributes['vault_id'])->account_id,
            'entity_type_id' => EntityType::factory(),
            'template_version_id' => TemplateVersion::factory()->published(),
            'name' => fake()->company(),
            'avatar_file_id' => null,
            'avatar_colour' => Entity::AVATAR_COLOURS[0],
            'is_archived' => false,
            'last_consulted_at' => null,
        ];
    }

    /**
     * Indicate that the record is hidden from the main lists without having
     * been deleted.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_archived' => true,
        ]);
    }
}
