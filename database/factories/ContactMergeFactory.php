<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SnapshotReasonEnum;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\RecordSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<ContactMerge> */
class ContactMergeFactory extends Factory
{
    protected $model = ContactMerge::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'kept_contact_id' => Contact::factory(),
            'merged_contact_id' => fn (array $attributes): int => Contact::factory()->create([
                'account_id' => Contact::query()->findOrFail($attributes['kept_contact_id'])->account_id,
                'vault_id' => Contact::query()->findOrFail($attributes['kept_contact_id'])->vault_id,
            ])->id,
            'account_id' => fn (array $attributes): int => Contact::query()->findOrFail($attributes['kept_contact_id'])->account_id,
            'vault_id' => fn (array $attributes): int => Contact::query()->findOrFail($attributes['kept_contact_id'])->vault_id,
            'resolutions' => [],
            'ledger' => [],
            'snapshot_id' => fn (array $attributes): int => RecordSnapshot::factory()->create([
                'snapshotable_type' => Contact::class,
                'snapshotable_id' => $attributes['kept_contact_id'],
                'reason' => SnapshotReasonEnum::Merge,
            ])->id,
            'merged_by_user_id' => null,
            'undone_at' => null,
            'undone_by_user_id' => null,
        ];
    }

    public function undoneBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'undone_at' => now(),
            'undone_by_user_id' => $user->id,
        ]);
    }
}
