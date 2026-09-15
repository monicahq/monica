<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SnapshotReasonEnum;
use App\Models\Contact;
use App\Models\RecordSnapshot;
use App\Models\StructureVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<RecordSnapshot> */
class RecordSnapshotFactory extends Factory
{
    protected $model = RecordSnapshot::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'snapshotable_type' => Contact::class,
            'snapshotable_id' => Contact::factory(),
            'account_id' => fn (array $attributes): int => Contact::query()->findOrFail($attributes['snapshotable_id'])->account_id,
            'vault_id' => fn (array $attributes): int => Contact::query()->findOrFail($attributes['snapshotable_id'])->vault_id,
            'structure_version_id' => fn (array $attributes): int => Contact::query()->findOrFail($attributes['snapshotable_id'])->structure_version_id,
            'reason' => SnapshotReasonEnum::Manual,
            'payload' => ['record' => [], 'structure' => [], 'rows' => [], 'values' => []],
            'file_ids' => [],
            'created_by_user_id' => null,
            'restored_at' => null,
            'expires_at' => null,
        ];
    }

    public function beforeAStructureChange(?StructureVersion $structureVersion = null): static
    {
        return $this->state(fn (array $attributes): array => array_filter([
            'reason' => SnapshotReasonEnum::TemplateChange,
            'structure_version_id' => $structureVersion?->id,
        ], fn (mixed $value): bool => $value !== null));
    }

    /** @param  list<int>  $fileIds */
    public function holding(array $fileIds): static
    {
        return $this->state(fn (array $attributes): array => [
            'file_ids' => $fileIds,
        ]);
    }
}
