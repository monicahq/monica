<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserToken;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserTokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = UserToken::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'driver' => 'github',
            'driver_id' => $this->faker->uuid,
            'format' => 'oauth2',
            'token' => $this->faker->word,
        ];
    }
}
