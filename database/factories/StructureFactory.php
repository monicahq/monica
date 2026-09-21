<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StructureAppliesToEnum;
use App\Models\Account;
use App\Models\EntityType;
use App\Models\Structure;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * A structure is always made by whoever owns it, so this factory expects an
 * owner rather than inventing one. Making a template here instead would make a
 * second structure for it, since a template creates its own.
 *
 * @extends Factory<Structure>
 */
class StructureFactory extends Factory
{
    protected $model = Structure::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'owner_type' => Template::class,
            'owner_id' => 0,
            'applies_to' => StructureAppliesToEnum::Contact,
            'entity_type_id' => null,
            'published_version_id' => null,
        ];
    }

    public function forEntity(?int $entityTypeId = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'applies_to' => StructureAppliesToEnum::Entity,
            'entity_type_id' => $entityTypeId ?? EntityType::factory(),
        ]);
    }

    public function forLifeEvent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'applies_to' => StructureAppliesToEnum::LifeEvent,
            'entity_type_id' => null,
        ]);
    }
}
