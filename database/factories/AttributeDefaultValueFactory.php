<?php

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\AttributeDefaultValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttributeDefaultValueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AttributeDefaultValue::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'attribute_id' => Attribute::factory(),
            'value' => 'male',
        ];
    }
}
