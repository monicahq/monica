<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactSlugReservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<ContactSlugReservation> */
class ContactSlugReservationFactory extends Factory
{
    protected $model = ContactSlugReservation::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'vault_id' => fn (array $attributes): int => Contact::query()->findOrFail($attributes['contact_id'])->vault_id,
            'slug' => $this->faker->unique()->slug(2),
        ];
    }
}
