<?php

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\Information;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Attribute::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'information_id' => Information::factory(),
            'name' => 'Gender',
            'type' => 'text',
        ];
    }
}
