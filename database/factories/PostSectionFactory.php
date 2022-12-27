<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostSection>
 */
class PostSectionFactory extends Factory
{
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
