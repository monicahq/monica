<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\GiftOccasion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GiftOccasion>
 */
class GiftOccasionFactory extends Factory
{
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
