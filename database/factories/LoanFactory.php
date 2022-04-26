<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'vault_id' => Vault::factory(),
            'type' => Loan::TYPE_DEBT,
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'amount_lent' => $this->faker->randomNumber(),
            'loaned_at' => $this->faker->dateTimeThisCentury(),
        ];
    }
}
