<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactInformation;
use App\Models\ContactInformationType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactInformationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
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
            'data' => $this->faker->name(),
        ];
    }
}
