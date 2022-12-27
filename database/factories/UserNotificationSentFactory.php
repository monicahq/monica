<?php

namespace Database\Factories;

use App\Models\UserNotificationChannel;
use App\Models\UserNotificationSent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserNotificationSent>
 */
class UserNotificationSentFactory extends Factory
{
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
