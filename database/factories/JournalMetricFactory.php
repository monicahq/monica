<?php

namespace Database\Factories;

use App\Models\Journal;
use App\Models\JournalMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JournalMetric>
 */
class JournalMetricFactory extends Factory
{
    protected $model = JournalMetric::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'journal_id' => Journal::factory(),
            'label' => $this->faker->sentence(),
        ];
    }
}
