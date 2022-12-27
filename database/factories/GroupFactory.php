<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupType;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'vault_id' => Vault::factory(),
            'group_type_id' => GroupType::factory(),
            'name' => $this->faker->name(),
        ];
    }
}
