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
    public function it_fetches_its_list_only_when_it_is_opened(): void
    {
        $user = $this->signedIn();
        $this->notification($user, 'Birthday, Michael Scott', now());

        $response = $this->get(route('settings.index'));

        $response->assertSee(route('notifications.index'));
        $response->assertSee('id="notification-list"', escape: false);
        $response->assertDontSee('Birthday, Michael Scott');
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
    public function it_is_reachable_and_announced_without_a_mouse(): void
    {
        $user = $this->signedIn();
        $this->notification($user, 'Birthday, Michael Scott', now());

        $response = $this->get(route('settings.index'));

        $response->assertSee('aria-haspopup="dialog"', escape: false);
        $response->assertSee('aria-controls="notification-bar-panel"', escape: false);
        $response->assertSee('aria-labelledby="notification-bar-heading"', escape: false);
        $response->assertSee('aria-live="polite"', escape: false);
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

    private function notification(User $user, string $title, mixed $when): Notification
    {
        return Notification::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'channel' => NotificationChannelEnum::InApp,
            'title' => $title,
            'sent_at' => $when,
            'created_at' => $when,
        ]);
    }
}
