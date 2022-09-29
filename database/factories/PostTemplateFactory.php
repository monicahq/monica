<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\PostTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
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
