<?php

namespace Database\Factories;

use App\Models\ModuleRow;
use App\Models\ModuleRowField;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModuleRowFieldFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ModuleRowField::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'module_row_id' => ModuleRow::factory(),
            'label' => $this->faker->word,
            'module_field_type' => ModuleRowField::TYPE_INPUT_TEXT,
            'required' => false,
            'position' => 1,
        ];
    }
}
