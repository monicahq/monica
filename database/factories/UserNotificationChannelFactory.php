<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserNotificationChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserNotificationChannelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
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
            'verified_at' => null,
            'preferred_time' => '09:00:00',
        ];
    }
}
