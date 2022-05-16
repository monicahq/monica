<?php

namespace Database\Factories;

use App\Models\ContactImportantDateType;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactImportantDateTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ContactImportantDateType::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'vault_id' => Vault::factory(),
            'label' => 'birthdate',
            'can_be_deleted' => true,
        ];
    }
}
