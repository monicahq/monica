<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RelationshipStatusEnum;
use App\Enums\RelationshipTypeEnum;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<Relationship> */
class RelationshipFactory extends Factory
{
    protected $model = Relationship::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'account_id' => fn (array $attributes): int => Vault::query()->findOrFail($attributes['vault_id'])->account_id,
            'relationship_type_id' => fn (array $attributes): int => RelationshipType::factory()
                ->create(['account_id' => $attributes['account_id']])
                ->id,
            'contact_a_id' => fn (array $attributes): int => Contact::factory()->create([
                'account_id' => $attributes['account_id'],
                'vault_id' => $attributes['vault_id'],
            ])->id,
            'contact_b_id' => fn (array $attributes): int => Contact::factory()->create([
                'account_id' => $attributes['account_id'],
                'vault_id' => $attributes['vault_id'],
            ])->id,
            'status' => RelationshipStatusEnum::Current,
            'started_on' => null,
            'ended_on' => null,
            'notes' => null,
        ];
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RelationshipStatusEnum::Ended,
        ]);
    }

    public function parentage(): static
    {
        return $this->state(fn (array $attributes): array => [
            'relationship_type_id' => RelationshipType::factory()
                ->system(RelationshipTypeEnum::ParentChild)
                ->create(['account_id' => $attributes['account_id']])
                ->id,
        ]);
    }

    public function romantic(): static
    {
        return $this->state(fn (array $attributes): array => [
            'relationship_type_id' => RelationshipType::factory()
                ->system(RelationshipTypeEnum::Partner)
                ->create(['account_id' => $attributes['account_id']])
                ->id,
        ]);
    }
}
