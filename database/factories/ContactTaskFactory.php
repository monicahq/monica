<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactTask>
 */
class ContactTaskFactory extends Factory
{
    protected $model = ContactTask::class;

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
            'author_name' => $this->faker->name,
            'label' => $this->faker->sentence(),
            'completed' => false,
            'due_at' => $this->faker->dateTimeThisCentury(),
            'uuid' => $this->faker->uuid,
        ];
    }
}
