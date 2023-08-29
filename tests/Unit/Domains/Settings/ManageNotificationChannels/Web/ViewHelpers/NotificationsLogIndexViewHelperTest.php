<?php

namespace Tests\Unit\Domains\Settings\ManageNotificationChannels\Web\ViewHelpers;

use App\Domains\Settings\ManageNotificationChannels\Web\ViewHelpers\NotificationsLogIndexViewHelper;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Models\UserNotificationSent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class NotificationsLogIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create();
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $user->id,
            'type' => UserNotificationChannel::TYPE_EMAIL,
            'label' => 'my label',
        ]);
        UserNotificationSent::factory()->create([
            'user_notification_channel_id' => $channel->id,
            'sent_at' => '2020-01-01 00:00:00',
            'subject_line' => 'subject line',
        ]);

        $array = NotificationsLogIndexViewHelper::data($channel, $user);

        $this->assertEquals(
            3,
            count($array)
        );

        $this->assertArrayHasKey('channel', $array);
        $this->assertArrayHasKey('notifications', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'channels' => env('APP_URL').'/settings/notifications',
                'back' => env('APP_URL').'/settings',
            ],
            $array['url']
        );
        $this->assertEquals(
            [
                'id' => $channel->id,
                'type' => 'Email address',
                'label' => 'my label',
            ],
            $array['channel']
        );
    }
}
