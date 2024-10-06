<?php

namespace Tests\Unit\Domains\Settings\ManageNotificationChannels\Services;

use App\Domains\Settings\ManageNotificationChannels\Services\VerifyUserNotificationChannelEmailAddress;
use App\Models\User;
use App\Models\UserNotificationChannel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VerifyUserNotificationChannelEmailAddressTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_verifies_the_user_notification_channel(): void
    {
        $ross = $this->createUser();
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $ross->id,
            'content' => 'admin@admin.com',
            'type' => UserNotificationChannel::TYPE_EMAIL,
        ]);
        $this->executeService($ross, $channel);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new VerifyUserNotificationChannelEmailAddress)->execute($request);
    }

    /** @test */
    public function it_fails_if_notification_channel_doesnt_belong_to_user(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $channel = UserNotificationChannel::factory()->create();
        $this->executeService($ross, $channel);
    }

    private function executeService(User $author, UserNotificationChannel $channel): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

        $request = [
            'account_id' => $author->account_id,
            'author_id' => $author->id,
            'user_notification_channel_id' => $channel->id,
            'uuid' => '21f74a1a-a157-11ec-b909-0242ac120002',
        ];

        $channel = (new VerifyUserNotificationChannelEmailAddress)->execute($request);

        $this->assertInstanceOf(
            UserNotificationChannel::class,
            $channel
        );

        $this->assertDatabaseHas('user_notification_channels', [
            'id' => $channel->id,
            'verified_at' => Carbon::now(),
        ]);
    }
}
