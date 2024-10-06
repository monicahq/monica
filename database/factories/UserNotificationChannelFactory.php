<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserNotificationChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserNotificationChannel>
 */
class UserNotificationChannelFactory extends Factory
{
    protected $model = UserNotificationChannel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'label' => $this->faker->word(),
            'content' => 'admin@admin.com',
            'active' => true,
            'fails' => 0,
            'verified_at' => null,
            'preferred_time' => '09:00:00',
        ];
    }
}
