<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\Streak;
use Illuminate\Database\Eloquent\Factories\Factory;

class StreakFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
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
