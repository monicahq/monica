<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StructureAppliesToEnum;
use App\Models\EntityType;
use App\Models\StructureVersion;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<VaultDefaultTemplate> */
class VaultDefaultTemplateFactory extends Factory
{
    protected $model = VaultDefaultTemplate::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'account_id' => fn (array $attributes): int => Vault::query()->findOrFail($attributes['vault_id'])->account_id,
            'applies_to' => StructureAppliesToEnum::Contact,
            'entity_type_id' => null,
            'structure_version_id' => StructureVersion::factory()->published(),
        ];
    }

    public function forEntity(?EntityType $entityType = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'applies_to' => StructureAppliesToEnum::Entity,
            'entity_type_id' => $entityType->id ?? EntityType::factory(),
        ]);
    }
}
