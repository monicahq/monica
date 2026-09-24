<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DatePrecisionEnum;
use App\Enums\LifeEventSourceEnum;
use App\Models\LifeEvent;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<LifeEvent> */
class LifeEventFactory extends Factory
{
    protected $model = LifeEvent::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'vault_id' => Vault::factory(),
            'account_id' => fn (array $attributes): int => Vault::query()->findOrFail($attributes['vault_id'])->account_id,
            'life_event_type_id' => fn (array $attributes): int => LifeEventType::factory()->create([
                'account_id' => $attributes['account_id'],
                'life_event_category_id' => LifeEventCategory::factory()->create(['account_id' => $attributes['account_id']])->id,
            ])->id,
            'structure_version_id' => fn (array $attributes): int => (int) LifeEventType::query()
                ->findOrFail($attributes['life_event_type_id'])
                ->structure?->published_version_id,
            'happened_on' => fake()->dateTimeBetween('2026-01-01', '2026-09-01')->format('Y-m-d'),
            'date_precision' => DatePrecisionEnum::Day,
            'title' => null,
            'narrative' => null,
            'location' => null,
            'duration_in_minutes' => null,
            'created_from' => LifeEventSourceEnum::Contact,
            'created_by_user_id' => null,
            'participant_count' => 0,
        ];
    }
}
