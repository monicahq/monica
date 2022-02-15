<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactDate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactDateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ContactDate::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'contact_id' => Contact::factory(),
            'label' => 'birthdate',
            'date' => '1981',
            'type' => ContactDate::TYPE_BIRTHDATE,
        ];
    }
}
