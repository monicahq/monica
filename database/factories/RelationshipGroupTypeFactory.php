<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\RelationshipGroupType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RelationshipGroupType>
 */
class RelationshipGroupTypeFactory extends Factory
{
    protected $model = RelationshipGroupType::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_id' => Account::factory(),
            'name' => $this->faker->name(),
        ];
    }
}
