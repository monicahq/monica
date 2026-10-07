<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactNameKindEnum;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<ContactNameRepresentation> */
class ContactNameRepresentationFactory extends Factory
{
    protected $model = ContactNameRepresentation::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'kind' => ContactNameKindEnum::Written,
            'language_tag' => null,
            'script_code' => null,
            'phonetic_system' => null,
            'formatted_name' => null,
            'prefix' => null,
            'given_name' => fake()->firstName(),
            'additional_name' => null,
            'family_name' => fake()->lastName(),
            'suffix' => null,
            'name_order' => null,
            'position' => 2,
        ];
    }

    public function transliteration(): static
    {
        return $this->state(fn (array $attributes): array => [
            'kind' => ContactNameKindEnum::Transliteration,
            'script_code' => 'Latn',
        ]);
    }

    public function phonetic(string $system = 'ipa'): static
    {
        return $this->state(fn (array $attributes): array => [
            'kind' => ContactNameKindEnum::Phonetic,
            'phonetic_system' => $system,
        ]);
    }
}
