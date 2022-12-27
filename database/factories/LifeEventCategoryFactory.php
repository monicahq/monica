<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\LifeEventCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LifeEventCategory>
 */
class LifeEventCategoryFactory extends Factory
{
    protected $model = LifeEventCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_id' => Account::factory(),
            'label' => $this->faker->name(),
            'label_translation_key' => $this->faker->name(),
            'can_be_deleted' => true,
        ];
    }
}
