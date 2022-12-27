<?php

namespace Database\Factories;

use App\Models\PostTemplate;
use App\Models\PostTemplateSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostTemplateSection>
 */
class PostTemplateSectionFactory extends Factory
{
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
