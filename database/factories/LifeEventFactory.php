<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventType;
use App\Models\TimelineEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LifeEvent>
 */
class LifeEventFactory extends Factory
{
    protected $model = LifeEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'timeline_event_id' => TimelineEvent::factory(),
            'life_event_type_id' => LifeEventType::factory(),
            'summary' => $this->faker->sentence(),
            'description' => $this->faker->sentence(),
            'happened_at' => $this->faker->dateTimeThisCentury(),
            'costs' => $this->faker->randomDigit(),
            'currency_id' => null,
            'paid_by_contact_id' => Contact::factory(),
            'duration_in_minutes' => $this->faker->randomNumber(),
            'distance' => $this->faker->randomNumber(),
            'from_place' => $this->faker->city(),
            'to_place' => $this->faker->city(),
            'place' => $this->faker->city(),
        ];
    }
}
