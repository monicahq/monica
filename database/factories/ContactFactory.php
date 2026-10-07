<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactNameKindEnum;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\Models\StructureVersion;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<Contact> */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'account_id' => fn (array $attributes): int => Vault::query()->findOrFail($attributes['vault_id'])->account_id,
            'structure_version_id' => StructureVersion::factory()->published(),
            'prefix' => null,
            'first_name' => fake()->firstName(),
            'middle_name' => null,
            'last_name' => fake()->lastName(),
            'suffix' => null,
            'maiden_name' => null,
            'nickname' => null,
            'name_order' => null,
            'is_me' => false,
            'is_partial' => false,
            'is_archived' => false,
            'is_favourite' => false,
            'avatar_file_id' => null,
            'avatar_colour' => Contact::AVATAR_COLOURS[0],
            'last_life_event_at' => null,
            'life_event_count' => 0,
            'last_consulted_at' => null,
        ];
    }

    /**
     * Give every named contact the display representation an action would have written, without
     * firing a single event, so a test counting queued jobs sees the same jobs as before.
     */
    #[Override]
    public function configure(): static
    {
        return $this->afterCreating(function (Contact $contact): void {
            $parts = [$contact->prefix, $contact->first_name, $contact->middle_name, $contact->last_name, $contact->suffix, $contact->formatted_name];

            if (array_filter($parts) === [] || $contact->display_name_representation_id !== null) {
                return;
            }

            $representation = new ContactNameRepresentation([
                'kind' => ContactNameKindEnum::Written,
                'formatted_name' => $contact->formatted_name,
                'prefix' => $contact->prefix,
                'given_name' => $contact->first_name,
                'additional_name' => $contact->middle_name,
                'family_name' => $contact->last_name,
                'suffix' => $contact->suffix,
                'name_order' => $contact->name_order,
                'position' => 1,
            ]);
            $representation->contact()->associate($contact);
            $representation->uuid = $representation->newUniqueId();
            $representation->writeKeys();
            $representation->saveQuietly();

            $contact->display_name_representation_id = $representation->id;
            $contact->saveQuietly();
        });
    }

    public function me(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_me' => true,
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_partial' => true,
            'last_name' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_archived' => true,
        ]);
    }

    public function favourite(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_favourite' => true,
        ]);
    }
}
