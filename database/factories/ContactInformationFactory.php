<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactInformation;
use App\Models\ContactInformationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactInformation>
 */
class ContactInformationFactory extends Factory
{
    protected $model = ContactInformation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'contact_id' => Contact::factory(),
            'type_id' => ContactInformationType::factory(),
            'data' => $this->faker->email(),
        ];
    }
}
