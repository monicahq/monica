<?php

namespace Database\Factories;

use App\Models\JournalMetric;
use App\Models\Post;
use App\Models\PostMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostMetric>
 */
class PostMetricFactory extends Factory
{
    protected $model = PostMetric::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'post_id' => Post::factory(),
            'journal_metric_id' => JournalMetric::factory(),
            'label' => $this->faker->sentence(),
            'value' => $this->faker->randomNumber(),
        ];
    }
}
