<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\EmailDeliveryStatusEnum;
use App\Enums\EmailType;
use App\Models\Account;
use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailSentControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'email_type',
                'subject',
                'email_address',
                'delivery_status',
                'sent_at',
                'delivered_at',
                'bounced_at',
                'complained_at',
                'created_at',
            ],
            'links' => ['self'],
        ],
    ];

    #[Test]
    public function it_lists_the_emails_sent_by_the_account_newest_first(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        EmailSent::factory()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'subject' => 'Your sign-in link',
            'sent_at' => '2026-08-20 09:00:00',
        ]);
        EmailSent::factory()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'subject' => 'A sign-in from a new place',
            'sent_at' => '2026-08-27 09:00:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/emails-sent');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.type', 'email_sent');
        $response->assertJsonPath('data.0.attributes.subject', 'A sign-in from a new place');
        $response->assertJsonPath('data.1.attributes.subject', 'Your sign-in link');
        $response->assertJsonPath('meta.page', 1);
        $response->assertJsonPath('meta.per_page', 25);
        $response->assertJsonPath('meta.total', 2);
    }

    #[Test]
    public function it_does_not_list_the_emails_sent_by_another_account(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        $stranger = User::factory()->create();
        EmailSent::factory()->create([
            'account_id' => $stranger->account_id,
            'user_id' => $stranger->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/emails-sent');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('meta.total', 0);
    }

    #[Test]
    public function it_lists_an_email_sent_to_somebody_without_an_account(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->withoutUser()->create([
            'account_id' => $user->account_id,
            'email_address' => 'creed.bratton@dundermifflin.com',
        ]);

        Sanctum::actingAs($user);

        $this->json('GET', '/api/v1/emails-sent')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.attributes.email_address', 'creed.bratton@dundermifflin.com');
    }

    #[Test]
    public function it_leaves_the_copy_of_the_email_out_of_the_list(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'body' => 'Somebody just signed in to your account.',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/emails-sent');

        $response->assertOk();
        $response->assertJsonMissingPath('data.0.attributes.body');
        $response->assertDontSee('Somebody just signed in to your account.');
    }

    #[Test]
    public function it_shows_one_email_that_was_sent(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        $emailSent = EmailSent::factory()->delivered()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'email_type' => EmailType::NewLogin->value,
            'subject' => 'A sign-in from a new place',
            'email_address' => 'michael.scott@dundermifflin.com',
            'sent_at' => '2026-08-27 09:00:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/emails-sent/{$emailSent->id}");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.type', 'email_sent');
        $response->assertJsonPath('data.id', (string) $emailSent->id);
        $response->assertJsonPath('data.attributes.email_type', EmailType::NewLogin->value);
        $response->assertJsonPath('data.attributes.subject', 'A sign-in from a new place');
        $response->assertJsonPath('data.attributes.email_address', 'michael.scott@dundermifflin.com');
        $response->assertJsonPath('data.attributes.delivery_status', EmailDeliveryStatusEnum::Delivered->value);
        $response->assertJsonPath('data.attributes.sent_at', $emailSent->sent_at->timestamp);
        $response->assertJsonPath('data.attributes.delivered_at', $emailSent->delivered_at->timestamp);
        $response->assertJsonPath('data.attributes.bounced_at', null);
        $response->assertJsonPath('data.links.self', route('api.v1.emailsSent.show', $emailSent->id));
    }

    #[Test]
    public function it_carries_the_copy_of_the_email_when_one_is_asked_for(): void
    {
        $user = User::factory()->create();
        $emailSent = EmailSent::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'body' => 'Somebody just signed in to your account from a device we have not seen before.',
        ]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/emails-sent/{$emailSent->id}")
            ->assertOk()
            ->assertJsonPath('data.attributes.body', 'Somebody just signed in to your account from a device we have not seen before.');
    }

    #[Test]
    public function it_returns_not_found_for_an_email_from_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $emailSent = EmailSent::factory()->create([
            'account_id' => $stranger->account_id,
            'user_id' => $stranger->id,
        ]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/emails-sent/{$emailSent->id}")->assertNotFound();
    }
}
