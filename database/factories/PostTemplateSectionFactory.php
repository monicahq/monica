<?php

namespace Database\Factories;

use App\Models\PostTemplate;
use App\Models\PostTemplateSection;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostTemplateSectionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = PostTemplateSection::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'post_template_id' => PostTemplate::factory(),
            'label' => 'business',
            'position' => 1,
        ];
    }
}
