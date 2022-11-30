<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\SyncToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SyncTokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
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
