<?php

namespace Database\Factories;

use App\Models\ImportError;
use App\Models\ImportJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImportError>
 */
class ImportErrorFactory extends Factory
{
    protected $model = ImportError::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'import_job_id' => ImportJob::factory(),
            'row_number' => $this->faker->numberBetween(1, 500),
            'error' => 'Missing required field: first_name',
        ];
    }
}
