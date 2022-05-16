<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

class VaultFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Vault::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_id' => Account::factory(),
            'type' => Vault::TYPE_PERSONAL,
            'name' => $this->faker->name(),
            'description' => $this->faker->sentence(),
        ];
    }
}
