<?php

namespace Database\Factories;

use App\Models\LifeMetric;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LifeMetric>
 */
class LifeMetricFactory extends Factory
{
    protected $model = LifeMetric::class;

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
        ];
    }
}
