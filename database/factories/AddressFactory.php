<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\AddressType;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'vault_id' => Vault::factory(),
            'address_type_id' => AddressType::factory(),
            'line_1' => $this->faker->streetAddress(),
            'line_2' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'province' => $this->faker->name(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->numberBetween(1, 3),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
        ];
    }
}
