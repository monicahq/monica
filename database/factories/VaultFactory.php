<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vault>
 */
class VaultFactory extends Factory
{
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
