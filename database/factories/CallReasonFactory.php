<?php

namespace Database\Factories;

use App\Models\CallReason;
use App\Models\CallReasonType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CallReason>
 */
class CallReasonFactory extends Factory
{
    protected $model = CallReason::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'call_reason_type_id' => CallReasonType::factory(),
            'label' => $this->faker->name(),
        ];
    }
}
