<?php

namespace Database\Factories;

use App\Models\RelationshipGroupType;
use App\Models\RelationshipType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RelationshipType>
 */
class RelationshipTypeFactory extends Factory
{
    protected $model = RelationshipType::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'relationship_group_type_id' => RelationshipGroupType::factory(),
            'name' => $this->faker->name(),
            'name_reverse_relationship' => $this->faker->name(),
        ];
    }
}
