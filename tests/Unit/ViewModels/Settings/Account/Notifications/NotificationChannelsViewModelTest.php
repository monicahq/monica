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

    #[Test]
    public function it_offers_to_remove_a_number_and_names_it_in_the_warning(): void
    {
        $user = $this->user();
        $this->channel($user, NotificationChannelEnum::Sms)->update(['destination' => '+15705550172']);

        $sms = $this->viewModel($user)->channels()->firstWhere('channel', 'sms');

        $this->assertTrue($sms['holds_setup']);
        $this->assertSame('The number +15705550172 will be erased from your account.', $sms['removal_warning']);
    }

    #[Test]
    public function it_offers_to_remove_a_number_still_waiting_for_its_code(): void
    {
        $user = $this->user();
        $this->channel($user, NotificationChannelEnum::Sms)->update([
            'destination' => '+15705550172',
            'proof_code_hash' => 'a hash',
            'proof_expires_at' => now()->addMinutes(10),
        ]);

        $this->assertTrue($this->viewModel($user)->channels()->firstWhere('channel', 'sms')['holds_setup']);
    }

    #[Test]
    public function it_offers_no_removal_on_a_channel_nobody_set_up(): void
    {
        $channels = $this->viewModel($this->user())->channels();

        $this->assertFalse($channels->firstWhere('channel', 'sms')['holds_setup']);
        $this->assertFalse($channels->firstWhere('channel', 'telegram')['holds_setup']);
        $this->assertFalse($channels->firstWhere('channel', 'email')['holds_setup']);
    }

    #[Test]
    public function it_warns_that_the_telegram_conversation_will_be_forgotten(): void
    {
        $user = $this->user();
        $this->channel($user, NotificationChannelEnum::Telegram)->update(['destination' => '424242']);

        $telegram = $this->viewModel($user)->channels()->firstWhere('channel', 'telegram');

        $this->assertTrue($telegram['holds_setup']);
        $this->assertSame('Your Telegram conversation will be forgotten.', $telegram['removal_warning']);
    }

    #[Test]
    public function it_offers_to_remove_a_number_kept_while_text_messages_are_withdrawn(): void
    {
        $pam = $this->user();
        $dwight = $this->user();
        $this->channel($pam, NotificationChannelEnum::Sms)->update(['destination' => '+15705550100']);
        $pam->account->features()->deactivate('sms-notifications');
        $dwight->account->features()->deactivate('sms-notifications');
        Feature::flushCache();

        $this->assertSame(
            [
                'url' => route('settings.notifications.setup.destroy', 'sms'),
                'warning' => 'The number +15705550100 will be erased from your account.',
            ],
            $this->viewModel($pam)->keptNumberRemoval(),
        );
        $this->assertNull($this->viewModel($dwight)->keptNumberRemoval());
    }

    #[Test]
    public function it_offers_another_code_once_the_wait_is_over(): void
    {
        $user = $this->user();
        $this->channel($user, NotificationChannelEnum::Sms)->update([
            'destination' => '+15705550172',
            'proof_sent_count' => 1,
            'proof_last_sent_at' => now()->subMinutes(2),
        ]);

        $sms = $this->viewModel($user)->channels()->firstWhere('channel', 'sms');

        $this->assertTrue($sms['can_send_proof_code']);
        $this->assertNull($sms['proof_code_refusal']);
    }

    #[Test]
    public function it_says_how_long_is_left_before_another_code(): void
    {
        $this->freezeTime();
        $user = $this->user();
        $this->channel($user, NotificationChannelEnum::Sms)->update([
            'destination' => '+15705550172',
            'proof_sent_count' => 1,
            'proof_last_sent_at' => now()->subSeconds(59),
        ]);

        $sms = $this->viewModel($user)->channels()->firstWhere('channel', 'sms');

        $this->assertFalse($sms['can_send_proof_code']);
        $this->assertSame('You can ask for another code in 1 second.', $sms['proof_code_refusal']);
    }

    #[Test]
    public function it_says_no_more_codes_will_be_sent_once_they_are_spent(): void
    {
        $user = $this->user();
        $this->channel($user, NotificationChannelEnum::Sms)->update([
            'destination' => '+15705550172',
            'proof_sent_count' => (int) config('monica.notification_proof_maximum_sends'),
            'proof_last_sent_at' => now()->subHour(),
        ]);

        $this->assertSame(
            'No more codes will be sent to this number. Remove it to type a number again.',
            $this->viewModel($user)->channels()->firstWhere('channel', 'sms')['proof_code_refusal'],
        );
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
