<?php

namespace Database\Factories;

use App\Models\Journal;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'journal_id' => Journal::factory(),
            'title' => $this->faker->sentence(),
            'published' => false,
            'written_at' => $this->faker->dateTimeThisCentury(),
        ];
    }
}
