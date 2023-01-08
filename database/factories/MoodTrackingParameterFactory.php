<?php

namespace Database\Factories;

use App\Models\MoodTrackingParameter;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MoodTrackingParameter>
 */
class MoodTrackingParameterFactory extends Factory
{
    protected $model = MoodTrackingParameter::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'vault_id' => Vault::factory(),
            'label' => $this->faker->name(),
            'hex_color' => $this->faker->name(),
            'position' => 1,
        ];
    }
}
