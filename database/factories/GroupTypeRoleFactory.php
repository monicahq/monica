<?php

namespace Database\Factories;

use App\Models\GroupType;
use App\Models\GroupTypeRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroupTypeRole>
 */
class GroupTypeRoleFactory extends Factory
{
    protected $model = GroupTypeRole::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'group_type_id' => GroupType::factory(),
            'label' => $this->faker->name,
            'position' => 1,
        ];
    }
}
