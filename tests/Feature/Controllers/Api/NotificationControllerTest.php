<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\NotificationChannelEnum;
use App\Models\Account;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'notification_type',
            'channel',
            'title',
            'body',
            'scheduled_for',
            'sent_at',
            'read_at',
            'failure_reason',
            'created_at',
        ],
        'links' => ['self'],
    ];

    #[Test]
    public function it_lists_what_the_application_has_said(): void
    {
        $user = $this->signedIn();
        $this->notification($user, 'Birthday, Michael Scott');

        $response = $this->json('GET', route('api.v1.notifications'));

        $response->assertOk();
        $response->assertJsonStructure(['data' => [$this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.title', 'Birthday, Michael Scott');
    }

    #[Test]
    public function it_can_be_narrowed_to_what_has_not_been_read(): void
    {
        $user = $this->signedIn();
        $this->notification($user, 'Unread one');
        $this->notification($user, 'Read one')->update(['read_at' => now()]);

        $response = $this->json('GET', route('api.v1.notifications', ['read' => 'false']));

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.title', 'Unread one');
    }

    #[Test]
    public function it_returns_one_by_its_address(): void
    {
        $user = $this->signedIn();
        $notification = $this->notification($user, 'Birthday, Michael Scott');

        $response = $this->json('GET', route('api.v1.notifications.show', $notification->uuid));

        $response->assertOk();
        $response->assertJsonPath('data.attributes.channel', 'in_app');
    }

    #[Test]
    public function it_marks_one_read(): void
    {
        $user = $this->signedIn();
        $notification = $this->notification($user, 'Birthday, Michael Scott');

        $response = $this->json('PUT', route('api.v1.notifications.read.update', $notification->uuid));

        $response->assertOk();
        $this->assertNotNull($notification->refresh()->read_at);
    }

    #[Test]
    public function it_marks_every_one_read(): void
    {
        $user = $this->signedIn();
        $this->notification($user, 'Birthday, Michael Scott');
        $this->notification($user, 'Anniversary, Jim Halpert');

        $response = $this->json('PUT', route('api.v1.notifications.readAll.update'));

        $response->assertOk();
        $response->assertJsonPath('data.marked', 2);
    }

    #[Test]
    public function it_never_lists_another_accounts_notifications(): void
    {
        $this->signedIn();
        $theirs = Account::factory()->create();
        $them = User::factory()->create(['account_id' => $theirs->id]);
        $this->notification($them, 'Their business');

        $this->json('GET', route('api.v1.notifications'))->assertJsonCount(0, 'data');
    }

    private function signedIn(): User
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        Sanctum::actingAs($user);

        return $user;
    }

    private function notification(User $user, string $title): Notification
    {
        return Notification::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'channel' => NotificationChannelEnum::InApp,
            'title' => $title,
            'sent_at' => now(),
        ]);
    }
}
