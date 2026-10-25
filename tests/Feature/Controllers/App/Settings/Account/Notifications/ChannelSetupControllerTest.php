<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Notifications;

use App\Enums\NotificationChannelEnum;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChannelSetupControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_the_number_and_says_so(): void
    {
        $user = $this->signedInWithNumber();

        $response = $this->delete(route('settings.notifications.setup.destroy', 'sms'));

        $response->assertRedirect(route('settings.notifications.index'));
        $response->assertSessionHas('status', 'The setup of Text message has been removed.');
        $this->assertNull($this->channel($user)->destination);
    }

    #[Test]
    public function email_answers_that_it_has_nothing_to_remove(): void
    {
        $this->signedInWithNumber();

        $response = $this->delete(route('settings.notifications.setup.destroy', 'email'));

        $response->assertSessionHasErrors(['channel' => 'This channel has nothing to remove.']);
    }

    #[Test]
    public function an_unknown_channel_is_not_found(): void
    {
        $this->signedInWithNumber();

        $this->delete(route('settings.notifications.setup.destroy', 'carrier-pigeon'))->assertNotFound();
    }

    #[Test]
    public function the_number_of_another_account_is_left_alone(): void
    {
        $theirs = $this->signedInWithNumber();
        $this->signedInWithNumber();

        $this->delete(route('settings.notifications.setup.destroy', 'sms'));

        $this->assertSame('+15705550172', $this->channel($theirs)->destination);
    }

    #[Test]
    public function a_number_kept_while_text_messages_are_withdrawn_can_still_be_removed(): void
    {
        $user = $this->signedInWithNumber();
        $user->account->features()->deactivate('sms-notifications');
        Feature::flushCache();

        $response = $this->delete(route('settings.notifications.setup.destroy', 'sms'));

        $response->assertRedirect(route('settings.notifications.index'));
        $this->assertNull($this->channel($user)->destination);
    }

    private function signedInWithNumber(): User
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $this->actingAs($user);

        $this->channel($user)->fill([
            'destination' => '+15705550172',
        ])->save();

        return $user;
    }

    private function channel(User $user): NotificationChannel
    {
        return NotificationChannel::query()
            ->where('account_id', $user->account_id)
            ->where('channel', NotificationChannelEnum::Sms->value)
            ->firstOrFail();
    }
}
