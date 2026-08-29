<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TemplateAppliesToEnum;
use App\Models\Account;
use App\Models\EntityType;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'applies_to' => TemplateAppliesToEnum::Contact,
            'entity_type_id' => null,
            'name' => 'Contact',
            'name_translation_key' => null,
            'description' => fake()->sentence(),
            'slug' => Str::slug(fake()->unique()->words(3, true)),
            'is_system' => false,
            'is_active' => true,
            'published_version_id' => null,
            'locale' => 'en',
            'position' => 1,
        ];
    }

    /**
     * Indicate that the template describes a kind of record the account
     * invented rather than a contact.
     */
    public function forEntity(?EntityType $entityType = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'applies_to' => TemplateAppliesToEnum::Entity,
            'entity_type_id' => $entityType->id ?? EntityType::factory(),
            'name' => 'Animal',
        ]);
    }

    /**
     * Indicate that the product supplied the template, which reads by its
     * translation key until the user renames it.
     */
    public function supplied(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => null,
            'name_translation_key' => 'Contact',
            'is_system' => true,
        ]);
    }

    /**
     * Indicate that the template is no longer offered for a new record, while
     * every record already on it stays readable and editable.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
