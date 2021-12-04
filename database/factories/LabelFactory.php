<?php

namespace Database\Factories;

use App\Models\Label;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class LabelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Label::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_id' => Account::factory(),
            'name' => $this->faker->name(),
            'slug' => $this->faker->name(),
            'description' => $this->faker->sentence(),
        ];
    }
}
