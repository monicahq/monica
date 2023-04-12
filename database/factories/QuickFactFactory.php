<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\QuickFact;
use App\Models\VaultQuickFactsTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuickFact>
 */
class QuickFactFactory extends Factory
{
    protected $model = QuickFact::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'vault_quick_facts_template_id' => VaultQuickFactsTemplate::factory(),
            'contact_id' => Contact::factory(),
            'content' => 'birthdate',
        ];
    }
}
