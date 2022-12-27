<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\ModuleRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ModuleRow>
 */
class ModuleRowFactory extends Factory
{
    protected $model = ModuleRow::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'module_id' => Module::factory(),
            'position' => 1,
        ];
    }
}
