<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\MoodTrackingEvent;
use App\Models\MoodTrackingParameter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MoodTrackingEvent>
 */
class MoodTrackingEventFactory extends Factory
{
    protected $model = MoodTrackingEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'contact_id' => Contact::factory(),
            'mood_tracking_parameter_id' => MoodTrackingParameter::factory(),
            'rated_at' => $this->faker->dateTime(),
            'note' => $this->faker->name(),
            'number_of_hours_slept' => $this->faker->randomNumber(),
        ];
    }
}
