<?php

namespace Database\Factories;

use App\Models\Template;
use App\Models\TemplatePage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TemplatePage>
 */
class TemplatePageFactory extends Factory
{
    protected $model = TemplatePage::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'template_id' => Template::factory(),
            'name' => 'business',
            'slug' => 'business',
            'position' => 1,
            'can_be_deleted' => true,
        ];
    }
}
