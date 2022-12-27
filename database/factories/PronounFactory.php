<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Pronoun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pronoun>
 */
class PronounFactory extends Factory
{
    protected $model = Pronoun::class;

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
        ];
    }
}
