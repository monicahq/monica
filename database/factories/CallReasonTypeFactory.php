<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\CallReasonType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CallReasonType>
 */
class CallReasonTypeFactory extends Factory
{
    protected $model = CallReasonType::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_id' => Account::factory(),
            'label' => $this->faker->name(),
        ];
    }
}
