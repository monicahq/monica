<?php

namespace Tests\Unit\Controllers\Settings\Notifications\ViewHelpers;

use function env;
use Tests\TestCase;
use App\Models\User;
use App\Models\UserNotificationChannel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\Settings\Notifications\ViewHelpers\NotificationsIndexViewHelper;

class NotificationsIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create();
        UserNotificationChannel::factory()->create([
            'user_id' => $user->id,
        ]);

        $array = NotificationsIndexViewHelper::data($user);

        $this->assertEquals(
            2,
            count($array)
        );

        $this->assertArrayHasKey('emails', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'back' => env('APP_URL').'/settings',
                'store' => env('APP_URL').'/settings/notifications',
            ],
            $array['url']
        );
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
        ]);

        $array = NotificationsIndexViewHelper::dtoEmail($channel, $user);
        $this->assertEquals(
            [
                'id' => $channel->id,
                'type' => 'email',
                'label' => 'super',
                'content' => 'admin@admin.com',
                'active' => $channel->active,
                'verified_at' => '2020-01-01 00:00:00',
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
}
