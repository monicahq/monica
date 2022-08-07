<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Emotion;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmotionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
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
