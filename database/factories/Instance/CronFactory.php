<?php

namespace Database\Factories\Instance;

use App\Models\Instance\Cron;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CronFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
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
