<?php

namespace Tests\Unit\Domains\Settings\ManageNotificationChannels\Web\ViewHelpers;

use App\Domains\Settings\ManageNotificationChannels\Web\ViewHelpers\NotificationsIndexViewHelper;
use App\Models\User;
use App\Models\UserNotificationChannel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class NotificationsIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        config(['services.telegram-bot-api.token' => null]);

        $user = User::factory()->create();
        UserNotificationChannel::factory()->create([
            'user_id' => $user->id,
        ]);

        $array = NotificationsIndexViewHelper::data($user);

        $this->assertEquals(
            3,
            count($array)
        );

        $this->assertArrayHasKey('emails', $array);
        $this->assertArrayHasKey('telegram', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'back' => env('APP_URL').'/settings',
                'store' => env('APP_URL').'/settings/notifications',
                'store_telegram' => env('APP_URL').'/settings/notifications/telegram',
            ],
            $array['url']
        );

        $this->assertFalse($array['telegram']['telegram_env_variable_set']);
    }

    /** @test */
    public function it_gets_the_data_needed_for_email_notification_channel(): void
    {
        $user = User::factory()->create();
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $user->id,
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'label' => 'super',
            'verified_at' => '2020-01-01 00:00:00',
            'preferred_time' => '09:00:00',
        ]);

        $array = NotificationsIndexViewHelper::dtoEmail($channel);
        $this->assertEquals(
            [
                'id' => $channel->id,
                'type' => 'email',
                'label' => 'super',
                'content' => 'admin@admin.com',
                'active' => $channel->active,
                'verified_at' => '2020-01-01 00:00:00',
                'preferred_time' => '09:00',
                'url' => [
                    'store' => env('APP_URL').'/settings/notifications',
                    'send_test' => env('APP_URL').'/settings/notifications/'.$channel->id.'/test',
                    'toggle' => env('APP_URL').'/settings/notifications/'.$channel->id.'/toggle',
                    'logs' => env('APP_URL').'/settings/notifications/'.$channel->id.'/logs',
                    'destroy' => env('APP_URL').'/settings/notifications/'.$channel->id,
                ],
            ],
            $array
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_telegram_notification_channel(): void
    {
        config(['services.telegram-bot-api.bot_url' => 'https://t.me/randombot']);

        $user = User::factory()->create();
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $user->id,
            'type' => UserNotificationChannel::TYPE_TELEGRAM,
            'label' => 'super',
            'verified_at' => '2020-01-01 00:00:00',
            'preferred_time' => '09:00:00',
        ]);

        $array = NotificationsIndexViewHelper::dtoTelegram($channel);
        $this->assertEquals(
            [
                'id' => $channel->id,
                'type' => 'telegram',
                'active' => true,
                'verified_at' => '2020-01-01 00:00:00',
                'preferred_time' => '09:00',
                'url' => [
                    'open' => 'https://t.me/randombot?start=',
                    'send_test' => env('APP_URL').'/settings/notifications/'.$channel->id.'/test',
                    'toggle' => env('APP_URL').'/settings/notifications/'.$channel->id.'/toggle',
                    'logs' => env('APP_URL').'/settings/notifications/'.$channel->id.'/logs',
                    'destroy' => env('APP_URL').'/settings/notifications/'.$channel->id,
                ],
            ],
            $array
        );
    }
}
