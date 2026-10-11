<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Notifications;

use App\Enums\NotificationChannelEnum;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\User;
use App\ViewModels\Settings\Account\Notifications\NotificationChannelsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationChannelsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_every_channel_in_the_order_the_product_names_them(): void
    {
        $channels = $this->viewModel($this->user())->channels();

        $this->assertSame(
            ['in_app', 'email', 'telegram', 'sms'],
            $channels->pluck('channel')->all(),
        );
    }

    #[Test]
    public function it_says_what_is_left_to_do_for_each_channel(): void
    {
        $channels = $this->viewModel($this->user())->channels();

        $this->assertContains(
            'Nothing to type. Following the link opens a conversation with our bot, and the bot tells us where to send.',
            $channels->firstWhere('channel', 'telegram')['steps'],
        );
        $this->assertContains(
            'Ask for a code, then type it back here. Until you do, nothing will be sent.',
            $channels->firstWhere('channel', 'sms')['steps'],
        );
        $this->assertContains(
            'Nothing to set up. Switch it on and it works.',
            $channels->firstWhere('channel', 'email')['steps'],
        );
    }

    #[Test]
    public function it_names_the_state_each_channel_is_in(): void
    {
        $user = $this->user();
        $this->channel($user, NotificationChannelEnum::Sms)->update([
            'destination' => '+15705550172',
        ]);
        $this->channel($user, NotificationChannelEnum::Telegram)->update([
            'disabled_reason' => 'Nothing was getting through.',
        ]);

        $channels = $this->viewModel($user)->channels();

        $this->assertSame('On', $channels->firstWhere('channel', 'email')['state']);

        $this->assertSame('Waiting for the code', $channels->firstWhere('channel', 'sms')['state']);
        $this->assertSame('Switched off after it stopped working', $channels->firstWhere('channel', 'telegram')['state']);
    }

    #[Test]
    public function it_counts_the_channels_that_actually_work(): void
    {
        $this->assertSame(2, $this->viewModel($this->user())->workingCount());
    }

    #[Test]
    public function it_sees_nothing_belonging_to_another_account(): void
    {
        $mine = $this->user();
        $this->user();

        $this->assertCount(4, $this->viewModel($mine)->channels());
    }

    #[Test]
    public function it_leaves_text_messages_out_while_sms_notifications_is_off_for_the_account(): void
    {
        $user = $this->user();
        $user->account->features()->deactivate('sms-notifications');
        Feature::flushCache();

        $this->assertSame(
            ['in_app', 'email', 'telegram'],
            $this->viewModel($user)->channels()->pluck('channel')->all(),
        );
    }

    #[Test]
    public function it_tells_only_an_account_that_gave_a_number_that_text_messages_are_unavailable(): void
    {
        $pam = $this->user();
        $dwight = $this->user();
        $this->channel($pam, NotificationChannelEnum::Sms)->update(['destination' => '+15705550100']);
        $pam->account->features()->deactivate('sms-notifications');
        $dwight->account->features()->deactivate('sms-notifications');
        Feature::flushCache();

        $this->assertSame(
            'Text messages are unavailable on this account for now. Your number is kept.',
            $this->viewModel($pam)->smsNotice(),
        );
        $this->assertNull($this->viewModel($dwight)->smsNotice());
    }

    #[Test]
    public function it_says_nothing_about_text_messages_while_they_are_offered(): void
    {
        $pam = $this->user();
        $this->channel($pam, NotificationChannelEnum::Sms)->update(['destination' => '+15705550100']);

        $this->assertNull($this->viewModel($pam)->smsNotice());
    }

    private function user(): User
    {
        $account = Account::factory()->create();

        return User::factory()->create(['account_id' => $account->id]);
    }

    private function viewModel(User $user): NotificationChannelsViewModel
    {
        return new NotificationChannelsViewModel(user: $user);
    }

    private function channel(User $user, NotificationChannelEnum $channel): NotificationChannel
    {
        return NotificationChannel::query()
            ->where('account_id', $user->account_id)
            ->where('channel', $channel->value)
            ->firstOrFail();
    }
}
