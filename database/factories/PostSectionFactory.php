<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostSection;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostSectionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = PostSection::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'post_id' => Post::factory(),
            'label' => 'label',
            'position' => $this->faker->numberBetween(1, 10),
            'content' => $this->faker->sentence(),
        ];
    }
}
