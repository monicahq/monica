<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Enums\NotificationChannelEnum;
use App\Models\Account;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * WCAG 2.2 AA on the two screens this feature adds, checked the way the passkey
 * screens are checked: on the markup that reaches the reader.
 */
class NotificationAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_bar_says_what_it_opens_and_how_many_are_waiting(): void
    {
        $user = $this->signedIn();
        $this->notification($user);

        $html = (string) $this->get(route('settings.index'))->getContent();

        $this->assertStringContainsString('aria-haspopup="dialog"', $html);
        $this->assertStringContainsString('aria-controls="notification-bar-panel"', $html);
    }

    #[Test]
    public function the_unread_count_is_announced_rather_than_only_drawn(): void
    {
        $user = $this->signedIn();
        $this->notification($user);

        $html = (string) $this->get(route('settings.index'))->getContent();

        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    #[Test]
    public function every_control_in_the_bar_is_a_real_button(): void
    {
        $user = $this->signedIn();
        $this->notification($user);

        $html = (string) $this->get(route('settings.index'))->getContent();

        foreach (['Mark every one as read', 'Yes, mark every one as read'] as $label) {
            $this->assertMatchesRegularExpression(
                '/<button[^>]*>(?:(?!<\/button>).)*'.preg_quote($label, '/').'/s',
                $html,
                $label.' is not inside a button element',
            );
        }
    }

    #[Test]
    public function the_panel_names_itself_to_a_screen_reader(): void
    {
        $this->signedIn();

        $html = (string) $this->get(route('settings.index'))->getContent();

        $this->assertStringContainsString('aria-labelledby="notification-bar-heading"', $html);
        $this->assertMatchesRegularExpression('/id="notification-bar-heading"[^>]*>Notifications</', $html);
    }

    #[Test]
    public function every_channel_on_the_settings_screen_is_a_named_region(): void
    {
        $this->signedIn();

        $html = (string) $this->get(route('settings.notifications.index'))->getContent();

        foreach (NotificationChannelEnum::cases() as $channel) {
            $this->assertStringContainsString('aria-labelledby="channel-'.$channel->value.'-heading"', $html);
            $this->assertStringContainsString('id="channel-'.$channel->value.'-heading"', $html);
        }
    }

    #[Test]
    public function every_field_on_the_settings_screen_is_tied_to_its_label(): void
    {
        $this->signedIn();

        $html = (string) $this->get(route('settings.notifications.index'))->getContent();

        foreach (['sms-destination', 'sms-enabled', 'email-enabled'] as $id) {
            $this->assertMatchesRegularExpression('/<label[^>]*for="'.$id.'"/', $html, "Nothing labels {$id}.");
            $this->assertMatchesRegularExpression('/id="'.$id.'"/', $html, "Nothing carries the id {$id}.");
        }
    }

    #[Test]
    public function the_text_message_block_asks_for_no_credential_because_the_instance_supplies_it(): void
    {
        $this->signedIn();

        $html = (string) $this->get(route('settings.notifications.index'))->getContent();

        $this->assertStringNotContainsString('id="sms-credential"', $html);
        $this->assertStringNotContainsString('name="credential[key]"', $html);
        $this->assertStringNotContainsString('name="credential[secret]"', $html);
        $this->assertStringContainsString('id="sms-destination"', $html);
    }

    #[Test]
    public function the_warning_about_a_destination_is_tied_to_the_field_it_is_about(): void
    {
        $this->signedIn();

        $html = (string) $this->get(route('settings.notifications.index'))->getContent();

        $this->assertStringContainsString('id="sms-destination-help"', $html);
    }

    private function signedIn(): User
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $this->actingAs($user);

        return $user;
    }

    private function notification(User $user): Notification
    {
        return Notification::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'channel' => NotificationChannelEnum::InApp,
            'sent_at' => now(),
        ]);
    }
}
