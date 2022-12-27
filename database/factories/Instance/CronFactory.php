<?php

namespace Database\Factories\Instance;

use App\Models\Instance\Cron;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Instance\Cron>
 */
class CronFactory extends Factory
{
    protected $model = Cron::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'command' => $this->faker->word,
            'last_run_at' => Carbon::now(),
        ];
    }
}
