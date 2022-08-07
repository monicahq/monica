<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactReminderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ContactReminder::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'contact_id' => Contact::factory(),
            'label' => $this->faker->sentence(),
            'day' => 29,
            'month' => 10,
            'year' => 1981,
            'type' => ContactReminder::TYPE_ONE_TIME,
            'frequency_number' => 1,
            'last_triggered_at' => null,
            'number_times_triggered' => 0,
        ];
    }
}
