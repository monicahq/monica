<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportJobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'user_id' => User::factory(),
            'vault_id' => Vault::factory(),
            'filename' => $this->faker->word() . '.csv',
            'file_path' => 'imports/' . $this->faker->uuid() . '.csv',
            'total_rows' => 0,
            'processed_rows' => 0,
            'failed_rows' => 0,
            'last_processed_row_index' => 0,
            'status' => 'pending',
        ];
    }
}
