<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Notifications;

use App\Enums\NotificationChannelEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\ChannelTestMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChannelTestControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_a_test_and_says_it_is_on_its_way(): void
    {
        NotificationFacade::fake();
        $user = $this->workingTextMessage();

        $response = $this->post(route('settings.notifications.test.store', 'sms'));

        $response->assertRedirect(route('settings.notifications.index'));
        $response->assertSessionHas('status', 'A test message is on its way.');
        NotificationFacade::assertSentTo($user, ChannelTestMessage::class);
    }

    #[Test]
    public function it_refuses_a_channel_that_is_not_finished(): void
    {
        NotificationFacade::fake();
        $this->workingTextMessage();

        $response = $this->post(route('settings.notifications.test.store', 'telegram'));

        $response->assertSessionHasErrors(['test' => 'Finish setting this channel up before testing it.']);
    }

    #[Test]
    public function it_logs_a_test_that_got_through(): void
    {
        NotificationFacade::fake();
        Queue::fake();
        $this->workingTextMessage();

        $this->post(route('settings.notifications.test.store', 'sms'));

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::NotificationChannelTested
                && $job->parameters === ['name' => 'Text message', 'sent' => true],
        );
    }

    private function workingTextMessage(): User
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $this->actingAs($user);

        NotificationChannel::query()
            ->where('account_id', $account->id)
            ->where('channel', NotificationChannelEnum::Sms->value)
            ->firstOrFail()
            ->fill([
                'is_enabled' => true,
                'destination' => '+15705550172',
                'proven_at' => now(),
            ])
            ->save();

        return $user;
    }
}
