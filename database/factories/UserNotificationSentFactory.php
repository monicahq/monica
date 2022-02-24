<?php

namespace Database\Factories;

use App\Models\UserNotificationSent;
use App\Models\UserNotificationChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserNotificationSentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserNotificationSent::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_notification_channel_id' => UserNotificationChannel::factory(),
            'sent_at' => $this->faker->dateTimeThisCentury(),
            'subject_line' => 'test',
        ];
    }
}
