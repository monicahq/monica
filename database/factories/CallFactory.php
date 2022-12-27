<?php

namespace Database\Factories;

use App\Models\Call;
use App\Models\CallReason;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Call>
 */
class CallFactory extends Factory
{
    protected $model = Call::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'contact_id' => Contact::factory(),
            'author_id' => User::factory(),
            'call_reason_id' => CallReason::factory(),
            'author_name' => $this->faker->name,
            'called_at' => $this->faker->dateTimeThisCentury(),
            'duration' => 100,
            'type' => Call::TYPE_AUDIO,
            'answered' => true,
            'who_initiated' => Call::INITIATOR_ME,
            'description' => $this->faker->sentence,
        ];
    }
}
