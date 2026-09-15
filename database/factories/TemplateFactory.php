<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StructureAppliesToEnum;
use App\Models\Account;
use App\Models\EntityType;
use App\Models\Structure;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Override;

/** @extends Factory<Template> */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => 'Contact',
            'name_translation_key' => null,
            'description' => fake()->sentence(),
            'slug' => Str::slug(fake()->unique()->words(3, true)),
            'is_system' => false,
            'is_active' => true,
            'locale' => 'en',
            'position' => 1,
        ];
    }

    public function forEntity(?EntityType $entityType = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Animal',
        ])->afterCreating(function (Template $template) use ($entityType): void {
            $template->structure->update([
                'applies_to' => StructureAppliesToEnum::Entity,
                'entity_type_id' => $entityType->id
                    ?? EntityType::factory()->create(['account_id' => $template->account_id])->id,
            ]);
        });
    }

    /**
     * Create the structure the template owns.
     *
     * A template without one cannot be given a version, so every template gets
     * one here rather than leaving each test to remember. It describes a contact
     * unless a state says otherwise, which forEntity does by amending it.
     */
    #[Override]
    public function configure(): static
    {
        return $this->afterCreating(function (Template $template): void {
            Structure::factory()->create([
                'account_id' => $template->account_id,
                'owner_type' => Template::class,
                'owner_id' => $template->id,
                'applies_to' => StructureAppliesToEnum::Contact,
                'entity_type_id' => null,
            ]);
        });
    }

    public function supplied(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => null,
            'name_translation_key' => 'Contact',
            'is_system' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
