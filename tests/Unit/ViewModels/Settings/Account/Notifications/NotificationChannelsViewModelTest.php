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

        $removal = $this->viewModel($pam)->keptNumberRemoval();

        $this->assertSame(route('settings.notifications.setup.destroy', 'sms'), $removal['url']);
        $this->assertStringContainsString('+15705550100', $removal['warning']);
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
