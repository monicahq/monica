<?php

namespace Database\Factories;

use App\Models\GroupType;
use App\Models\GroupTypeRole;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupTypeRoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
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
