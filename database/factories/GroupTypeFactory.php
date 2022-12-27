<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\GroupType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroupType>
 */
class GroupTypeFactory extends Factory
{
    protected $model = GroupType::class;

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
