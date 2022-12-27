<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\SyncToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SyncToken>
 */
class SyncTokenFactory extends Factory
{
    protected $model = SyncToken::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'account_id' => Account::factory(),
            'user_id' => User::factory(),
            'name' => Str::orderedUuid(),
            'timestamp' => $this->faker->dateTimeThisCentury,
        ];
    }
}
