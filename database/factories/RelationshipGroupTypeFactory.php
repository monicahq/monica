<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\RelationshipGroupType;
use Illuminate\Database\Eloquent\Factories\Factory;

class RelationshipGroupTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
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
