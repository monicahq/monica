<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\TemplateVersion;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    /**
     * A contact in one account living in the vault of another is nothing, so
     * the account is taken from the vault rather than made beside it.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'account_id' => fn (array $attributes): int => Vault::query()->findOrFail($attributes['vault_id'])->account_id,
            'template_version_id' => TemplateVersion::factory()->published(),
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
            'last_activity_at' => null,
            'activity_count' => 0,
            'last_consulted_at' => null,
        ];
    }

    /**
     * Indicate that the contact represents the person using the product.
     */
    public function me(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_me' => true,
        ]);
    }

    /**
     * Indicate that the contact was mentioned in a relationship and is not
     * filled in yet, which keeps them out of the main lists.
     */
    public function partial(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_partial' => true,
            'last_name' => null,
        ]);
    }

    /**
     * Indicate that the contact is hidden from the main lists without having
     * been deleted.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_archived' => true,
        ]);
    }

    /**
     * Indicate that the contact is pinned to the top of the list.
     */
    public function favourite(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_favourite' => true,
        ]);
    }
}
