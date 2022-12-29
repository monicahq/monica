<?php

namespace Database\Factories;

use App\Models\Journal;
use App\Models\SliceOfLife;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SliceOfLife>
 */
class SliceOfLifeFactory extends Factory
{
    protected $model = SliceOfLife::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'journal_id' => Journal::factory(),
            'name' => $this->faker->name(),
        ];
    }
}
