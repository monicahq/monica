<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\Streak;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Streak>
 */
class StreakFactory extends Factory
{
    protected $model = Streak::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'goal_id' => Goal::factory(),
            'happened_at' => $this->faker->dateTimeThisCentury,
        ];
    }
}
