<?php

namespace Database\Factories;

use App\Models\Vault;
use App\Models\VaultQuickFactsTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VaultQuickFactsTemplate>
 */
class VaultQuickFactsTemplateFactory extends Factory
{
    protected $model = VaultQuickFactsTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'vault_id' => Vault::factory(),
            'label' => $this->faker->name(),
            'position' => 1,
            'label_translation_key' => 'vault_quick_fact_template.label_translation_key',
        ];
    }
}
