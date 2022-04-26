<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactImportantDate;
use App\Models\ContactImportantDateType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactImportantDateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ContactImportantDate::class;

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
            'day' => 29,
            'month' => 10,
            'year' => 1981,
            'contact_important_date_type_id' => ContactImportantDateType::factory(),
        ];
    }
}
