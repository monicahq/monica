<?php

namespace Database\Factories;

use App\Models\Journal;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
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
            'content' => $this->faker->name(),
            'written_at' => $this->faker->dateTimeThisCentury(),
        ];
    }
}
