<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactTaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
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
        ];
    }
}
