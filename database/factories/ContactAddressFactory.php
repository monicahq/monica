<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\AddressType;
use App\Models\ContactAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactAddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ContactAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'contact_id' => Contact::factory(),
            'address_type_id' => AddressType::factory(),
        ];
    }
}
