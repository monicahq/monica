<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\ContactInformationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactInformationType>
 */
class ContactInformationTypeFactory extends Factory
{
    protected $model = ContactInformationType::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_id' => Account::factory(),
            'name' => $this->faker->name(),
            'protocol' => 'mailto:',
            'can_be_deleted' => false,
            'type' => 'email',
        ];
    }
}
