<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\PostTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostTemplate>
 */
class PostTemplateFactory extends Factory
{
    protected $model = PostTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_id' => Account::factory(),
            'label' => 'business',
            'position' => 1,
            'can_be_deleted' => false,
        ];
    }
}
