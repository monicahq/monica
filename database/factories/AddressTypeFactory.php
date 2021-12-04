<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AddressType;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
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
        ];
    }
}
