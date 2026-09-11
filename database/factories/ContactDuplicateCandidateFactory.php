<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactDuplicateCandidate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<ContactDuplicateCandidate> */
class ContactDuplicateCandidateFactory extends Factory
{
    protected $model = ContactDuplicateCandidate::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'other_contact_id' => fn (array $attributes): int => Contact::factory()->create([
                'vault_id' => Contact::query()->findOrFail($attributes['contact_id'])->vault_id,
            ])->id,
            'account_id' => fn (array $attributes): int => Contact::query()->findOrFail($attributes['contact_id'])->account_id,
            'vault_id' => fn (array $attributes): int => Contact::query()->findOrFail($attributes['contact_id'])->vault_id,
            'score' => 70,
            'reasons' => [['kind' => 'name']],
            'dismissed_at' => null,
            'dismissed_by_user_id' => null,
            'computed_at' => now(),
        ];
    }

    public function dismissedBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'dismissed_at' => now(),
            'dismissed_by_user_id' => $user->id,
        ]);
    }

    public function sharingAValue(string $fieldKey = 'email', string $value = 'jim@dundermifflin.com'): static
    {
        return $this->state(fn (array $attributes): array => [
            'score' => 95,
            'reasons' => [['kind' => 'field', 'field_key' => $fieldKey, 'value' => $value]],
        ]);
    }
}
