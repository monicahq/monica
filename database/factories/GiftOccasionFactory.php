<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\GiftOccasion;
use Illuminate\Database\Eloquent\Factories\Factory;

class GiftOccasionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = GiftOccasion::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_id' => Account::factory(),
            'label' => $this->faker->name,
            'position' => 1,
        ];
    }
}
