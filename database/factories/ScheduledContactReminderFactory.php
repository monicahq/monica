<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\ContactReminder;
use App\Models\ScheduledContactReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledContactReminderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduledContactReminder::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'contact_reminder_id' => ContactReminder::factory(),
            'triggered_at' => $this->faker->dateTimeThisCentury(),
            'triggered' => false,
        ];
    }
}
