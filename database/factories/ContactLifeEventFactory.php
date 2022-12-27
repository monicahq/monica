<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactLifeEvent;
use App\Models\LifeEventType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactLifeEvent>
 */
class ContactLifeEventFactory extends Factory
{
    protected $model = ContactLifeEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'contact_id' => Contact::factory(),
            'life_event_type_id' => LifeEventType::factory(),
            'summary' => $this->faker->word(),
            'started_at' => $this->faker->dateTimeThisCentury(),
            'ended_at' => $this->faker->dateTimeThisCentury(),
        ];
    }
}
