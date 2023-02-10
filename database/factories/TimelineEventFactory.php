<?php

namespace Database\Factories;

use App\Models\TimelineEvent;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimelineEvent>
 */
class TimelineEventFactory extends Factory
{
    protected $model = TimelineEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'vault_id' => Vault::factory(),
            'started_at' => $this->faker->dateTimeThisCentury(),
            'label' => $this->faker->sentence(),
        ];
    }
}
