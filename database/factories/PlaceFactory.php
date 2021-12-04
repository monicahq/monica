<?php

namespace Database\Factories;

use App\Models\Place;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlaceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Place::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'street' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'province' => $this->faker->name(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->numberBetween(1, 3),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
        ];
    }
}
