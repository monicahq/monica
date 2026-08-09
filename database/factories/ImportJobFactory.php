<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\ImportJob;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImportJob>
 */
class ImportJobFactory extends Factory
{
    protected $model = ImportJob::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_id' => Account::factory(),
            'user_id' => User::factory(),
            'vault_id' => Vault::factory(),
            'filename' => 'contacts.csv',
            'file_path' => 'imports/test/test.csv',
            'total_rows' => 0,
            'processed_rows' => 0,
            'failed_rows' => 0,
            'status' => ImportJob::STATUS_PENDING,
        ];
    }

    /**
     * Indicate the import is in processing state.
     */
    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => ImportJob::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
    }

    /**
     * Indicate the import is completed.
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => ImportJob::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate the import failed.
     */
    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => ImportJob::STATUS_FAILED,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'failure_message' => 'Import processing failed.',
        ]);
    }
}
