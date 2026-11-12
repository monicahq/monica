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
