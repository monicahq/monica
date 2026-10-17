<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App;

use App\Enums\NotificationChannelEnum;
use App\Models\Account;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_draws_the_list_the_bar_fetches_when_it_is_opened(): void
    {
        $user = $this->signedIn();
        $this->notification($user, 'Birthday, Michael Scott');

        $response = $this->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('id="notification-list"', escape: false);
        $response->assertSee('Birthday, Michael Scott');
        $response->assertSee('Not read yet.');
    }

    #[Test]
    public function the_empty_list_is_a_sentence(): void
    {
        $this->signedIn();

        $this->get(route('notifications.index'))->assertSee('Nothing to catch up on.');
    }

    #[Test]
    public function a_full_page_offers_to_load_more(): void
    {
        $user = $this->signedIn();
        $this->thirtyNotifications($user);

        $response = $this->get(route('notifications.index'));

        $response->assertSee('Anniversary, Jim Halpert');
        $response->assertDontSee('Birthday, Michael Scott');
        $response->assertSee(__('Load more'));
        $response->assertDontSee(__('End of list'));
    }

    #[Test]
    public function the_last_page_holds_the_rest_and_says_it_is_the_end(): void
    {
        $user = $this->signedIn();
        $this->thirtyNotifications($user);

        $response = $this->get(route('notifications.index', ['page' => 2]));

        $response->assertSee('Birthday, Michael Scott');
        $response->assertDontSee('Anniversary, Jim Halpert');
        $this->assertSame(5, substr_count((string) $response->getContent(), 'Birthday, Michael Scott'));
        $response->assertSee(__('End of list'));
        $response->assertDontSee(__('Load more'));
    }

    #[Test]
    public function a_short_list_offers_nothing_more(): void
    {
        $user = $this->signedIn();
        $this->notification($user, 'Birthday, Michael Scott');
        $this->notification($user, 'Anniversary, Jim Halpert');
        $this->notification($user, 'Birthday, Dwight Schrute');

        $response = $this->get(route('notifications.index'));

        $response->assertDontSee(__('Load more'));
        $response->assertDontSee(__('End of list'));
    }

    #[Test]
    public function opening_one_marks_it_read_and_lands_on_the_record(): void
    {
        $user = $this->signedIn();
        $notification = $this->notification($user, 'Birthday, Michael Scott');

        $response = $this->put(route('notifications.read.update', $notification->uuid));

        $response->assertRedirect();
        $this->assertNotNull($notification->refresh()->read_at);
    }

    #[Test]
    public function marking_one_read_twice_keeps_the_instant_it_was_first_read(): void
    {
        $user = $this->signedIn();
        $notification = $this->notification($user, 'Birthday, Michael Scott');

        $this->put(route('notifications.read.update', $notification->uuid));
        $first = $notification->refresh()->read_at;

        $this->travel(5)->minutes();
        $this->put(route('notifications.read.update', $notification->uuid));

        $this->assertTrue($first->equalTo($notification->refresh()->read_at));
    }

    #[Test]
    public function clearing_everything_marks_every_unread_one(): void
    {
        $user = $this->signedIn();
        $this->notification($user, 'Birthday, Michael Scott');
        $this->notification($user, 'Anniversary, Jim Halpert');

        $response = $this->put(route('notifications.readAll.update'));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Everything is marked as read.');
        $this->assertSame(0, Notification::query()->where('user_id', $user->id)->whereNull('read_at')->count());
    }

    #[Test]
    public function clearing_everything_leaves_another_account_alone(): void
    {
        $this->signedIn();
        $theirs = Account::factory()->create();
        $them = User::factory()->create(['account_id' => $theirs->id]);
        $notification = $this->notification($them, 'Their business');

        $this->put(route('notifications.readAll.update'));

        $this->assertNull($notification->refresh()->read_at);
    }

    private function signedIn(): User
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $this->actingAs($user);

        return $user;
    }

    private function thirtyNotifications(User $user): void
    {
        foreach (range(1, 25) as $minutesAgo) {
            $this->notification($user, 'Anniversary, Jim Halpert', now()->subMinutes($minutesAgo));
        }

        foreach (range(26, 30) as $minutesAgo) {
            $this->notification($user, 'Birthday, Michael Scott', now()->subMinutes($minutesAgo));
        }
    }

    private function notification(User $user, string $title, mixed $when = null): Notification
    {
        return Notification::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'channel' => NotificationChannelEnum::InApp,
            'title' => $title,
            'sent_at' => $when ?? now(),
            'created_at' => $when ?? now(),
        ]);
    }
}
