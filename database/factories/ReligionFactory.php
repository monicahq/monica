<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Religion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Religion>
 */
class ReligionFactory extends Factory
{
    protected $model = Religion::class;

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
            'translation_key' => $this->faker->word(),
            'position' => 1,
        ];
    }
}
