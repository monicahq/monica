<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactReminderControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'name',
            'source',
            'starts_on',
            'field',
            'group',
            'cadence',
            'interval',
            'time',
            'timezone',
            'is_active',
            'inactive_reason',
            'next_date',
            'created_at',
        ],
        'links' => ['self'],
    ];

    #[Test]
    public function it_lists_the_reminders_about_a_contact(): void
    {
        [$user, $vault, $contact] = $this->michaelAndPam();
        Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create(['name' => 'Ask about the art school']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/reminders");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.name', 'Ask about the art school');
        $response->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function it_does_not_find_a_contact_of_another_account(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$contact->vault->uuid}/contacts/{$contact->uuid}/reminders")->assertNotFound();
    }

    #[Test]
    public function it_writes_a_reminder_about_a_contact(): void
    {
        [$user, $vault, $contact] = $this->michaelAndPam();

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/reminders", [
            'name' => 'Ask about the art school',
            'starts_on' => now()->addWeek()->toDateString(),
            'cadence' => 'monthly',
            'time' => '09:00',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.source', 'manual');
        $this->assertDatabaseHas('reminders', ['remindable_id' => $contact->id, 'name' => 'Ask about the art school', 'source' => 'manual']);
    }

    #[Test]
    public function it_refuses_a_reminder_that_starts_in_the_past(): void
    {
        [$user, $vault, $contact] = $this->michaelAndPam();

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/reminders", [
            'name' => 'Ask about the art school',
            'starts_on' => now()->subWeek()->toDateString(),
            'cadence' => 'once',
            'time' => '09:00',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['starts_on']);
    }

    #[Test]
    public function it_shows_one_reminder(): void
    {
        [$user, $vault, $contact] = $this->michaelAndPam();
        $reminder = Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create();

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/reminders/{$reminder->uuid}");

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.id', $reminder->uuid);
    }

    #[Test]
    public function it_does_not_find_a_reminder_about_another_contact_of_the_vault(): void
    {
        [$user, $vault, $pam] = $this->michaelAndPam();
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jim']);
        $reminder = Reminder::factory()->manual($jim, now()->addWeek()->toDateString())->create();

        Sanctum::actingAs($user);

        $url = "/api/v1/vaults/{$vault->uuid}/contacts/{$pam->uuid}/reminders/{$reminder->uuid}";

        $this->json('GET', $url)->assertNotFound();
        $this->json('DELETE', $url)->assertNotFound();
        $this->assertModelExists($reminder);
    }

    #[Test]
    public function it_changes_a_reminder(): void
    {
        [$user, $vault, $contact] = $this->michaelAndPam();
        $reminder = Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create(['frequency_interval' => 3]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/reminders/{$reminder->uuid}", [
            'name' => 'Congratulate her on the gallery',
            'cadence' => 'yearly',
            'time' => '10:00',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', 'Congratulate her on the gallery');
        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'name' => 'Congratulate her on the gallery', 'cadence' => 'yearly', 'frequency_interval' => 3]);
    }

    #[Test]
    public function it_refuses_a_first_date_on_a_reminder_reading_a_date_on_the_contact(): void
    {
        [$user, $vault, $contact] = $this->michaelAndPam();
        $value = FieldValue::factory()->recurringDate()->create(['valuable_id' => $contact->id]);
        $reminder = Reminder::factory()->create(['field_value_id' => $value->id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/reminders/{$reminder->uuid}", [
            'name' => 'Pam’s birthday',
            'starts_on' => now()->addWeek()->toDateString(),
            'cadence' => 'yearly',
            'time' => '09:00',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['starts_on']);
    }

    #[Test]
    public function it_deletes_a_reminder(): void
    {
        [$user, $vault, $contact] = $this->michaelAndPam();
        $reminder = Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create();

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/reminders/{$reminder->uuid}")->assertNoContent();

        $this->assertSoftDeleted($reminder);
    }

    /** @return array{0: User, 1: Vault, 2: Contact} */
    private function michaelAndPam(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Pam']);

        return [$user, $vault, $contact];
    }
}
