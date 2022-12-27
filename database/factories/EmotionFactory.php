<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Emotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Emotion>
 */
class EmotionFactory extends Factory
{
    protected $model = Emotion::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_id' => Account::factory(),
            'name' => $this->faker->firstName,
            'type' => $this->faker->firstName,
        ];
    }
}
