<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AddressType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AddressType>
 */
class AddressTypeFactory extends Factory
{
    protected $model = AddressType::class;

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
            'type' => $this->faker->randomElement(['home', 'work', 'other']),
        ];
    }
}
