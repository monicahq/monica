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

class NotificationBarTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_drawn_on_a_signed_in_screen(): void
    {
        $this->signedIn();

        $response = $this->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('id="notification-bar-panel"', escape: false);
        $response->assertSee('Notifications, 0 unread');
    }

    #[Test]
    public function the_empty_state_is_a_sentence(): void
    {
        $this->signedIn();

        $this->get(route('settings.index'))->assertSee('Nothing to catch up on.');
    }

    #[Test]
    public function it_lists_what_the_application_has_said_newest_first(): void
    {
        $user = $this->signedIn();
        $this->notification($user, 'Birthday, Michael Scott', now()->subDays(2));
        $this->notification($user, 'Anniversary, Jim Halpert', now()->subHour());

        $response = $this->get(route('settings.index'));

        $response->assertSeeInOrder(['Anniversary, Jim Halpert', 'Birthday, Michael Scott']);
        $response->assertDontSee('Nothing to catch up on.');
    }

    #[Test]
    public function it_counts_what_has_not_been_read(): void
    {
        $user = $this->signedIn();
        $this->notification($user, 'Birthday, Michael Scott', now());
        $this->notification($user, 'Anniversary, Jim Halpert', now())->update(['read_at' => now()]);

        $response = $this->get(route('settings.index'));

        $response->assertSee('Notifications, 1 unread');
        $response->assertSee('1 notification you have not read');
    }

    #[Test]
    public function it_shows_only_what_went_out_in_the_application(): void
    {
        $user = $this->signedIn();
        $this->notification($user, 'Sent by email only', now(), NotificationChannelEnum::Email);

        $this->get(route('settings.index'))->assertDontSee('Sent by email only');
    }

    #[Test]
    public function it_shows_nothing_belonging_to_another_account(): void
    {
        $this->signedIn();

        $theirs = Account::factory()->create();
        $them = User::factory()->create(['account_id' => $theirs->id]);
        $this->notification($them, 'Their business', now());

        $this->get(route('settings.index'))->assertDontSee('Their business');
    }

    #[Test]
    public function it_is_reachable_and_announced_without_a_mouse(): void
    {
        $user = $this->signedIn();
        $this->notification($user, 'Birthday, Michael Scott', now());

        $response = $this->get(route('settings.index'));

        $response->assertSee('aria-haspopup="dialog"', escape: false);
        $response->assertSee('aria-controls="notification-bar-panel"', escape: false);
        $response->assertSee('aria-labelledby="notification-bar-heading"', escape: false);
        $response->assertSee('aria-live="polite"', escape: false);
        $response->assertSee('Not read yet.');
    }

    #[Test]
    public function clearing_everything_asks_first(): void
    {
        $user = $this->signedIn();
        $this->notification($user, 'Birthday, Michael Scott', now());

        $response = $this->get(route('settings.index'));

        $response->assertSee('Mark every one as read');
        $response->assertSee('Yes, mark every one as read');
    }

    #[Test]
    public function there_is_nothing_to_clear_when_everything_is_read(): void
    {
        $user = $this->signedIn();
        $this->notification($user, 'Birthday, Michael Scott', now())->update(['read_at' => now()]);

        $this->get(route('settings.index'))->assertDontSee('Mark every one as read');
    }

    private function signedIn(): User
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $this->actingAs($user);

        return $user;
    }

    private function notification(User $user, string $title, mixed $when, NotificationChannelEnum $channel = NotificationChannelEnum::InApp): Notification
    {
        return Notification::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'channel' => $channel,
            'title' => $title,
            'sent_at' => $when,
            'created_at' => $when,
        ]);
    }
}
