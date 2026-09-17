<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DefaultPageEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Account;
use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\Structure;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<ActivityType> */
class ActivityTypeFactory extends Factory
{
    protected $model = ActivityType::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'activity_category_id' => ActivityCategory::factory(),
            'key' => null,
            'name' => fake()->word(),
            'name_translation_key' => null,
            'description' => null,
            'icon' => null,
            'colour' => null,
            'is_system' => false,
            'is_active' => true,
            'position' => 1,
        ];
    }

    /**
     * A type owns the structure its custom fields live in, and that structure
     * publishes one version holding one empty page. Anything reading a type's
     * fields meets that shape, so the factory builds it rather than leaving a
     * half a type nothing in the application would ever produce.
     */
    #[Override]
    public function configure(): static
    {
        return $this->afterCreating(function (ActivityType $activityType): void {
            $structure = Structure::query()->create([
                'account_id' => $activityType->account_id,
                'owner_type' => ActivityType::class,
                'owner_id' => $activityType->id,
                'applies_to' => StructureAppliesToEnum::Activity,
                'entity_type_id' => null,
                'published_version_id' => null,
            ]);

            $version = StructureVersion::query()->create([
                'account_id' => $activityType->account_id,
                'structure_id' => $structure->id,
                'version_number' => 1,
                'status' => VersionStatusEnum::Draft,
                'change_note' => null,
                'published_at' => null,
                'retired_at' => null,
                'previous_version_id' => null,
                'record_count' => 0,
            ]);

            StructureVersionPage::query()->create([
                'account_id' => $activityType->account_id,
                'structure_version_id' => $version->id,
                'key' => StructureVersionPage::SUPPLIED_KEY,
                'label' => null,
                'label_translation_key' => DefaultPageEnum::General->label(),
                'position' => 1,
            ]);

            $version->status = VersionStatusEnum::Published;
            $version->published_at = now();
            $version->save();

            $structure->published_version_id = $version->id;
            $structure->save();
        });
    }

    public function supplied(): static
    {
        return $this->state(fn (array $attributes): array => [
            'key' => 'call',
            'name' => null,
            'name_translation_key' => 'Call',
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
