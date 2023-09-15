<?php

namespace Database\Factories;

use App\Models\ContactImportantDate;
use App\Models\ContactImportantDateType;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactImportantDateType>
 */
class ContactImportantDateTypeFactory extends Factory
{
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
            'label' => 'Birthdate',
            'internal_type' => ContactImportantDate::TYPE_BIRTHDATE,
            'can_be_deleted' => true,
        ];
    }
}
