<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactReminderOccurrenceControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => ['reminder', 'name', 'date', 'scheduled_for', 'category', 'answer', 'answered_at', 'cancelled_reason'],
        'links' => ['reminder'],
    ];

    #[Test]
    public function it_lists_the_answered_dates_in_the_history(): void
    {
        [$user, $vault, $contact, $reminder] = $this->pamWithAReminder();
        ReminderOccurrence::factory()->past()->create(['reminder_id' => $reminder->id]);
        $answered = ReminderOccurrence::factory()->daysFromNow(-40)->forgotten()->create(['reminder_id' => $reminder->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/reminder-occurrences?category=history");

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $answered->uuid);
        $response->assertJsonPath('data.0.attributes.answer', 'forgotten');
        $response->assertJsonPath('data.0.attributes.category', null);
    }

    #[Test]
    public function it_refuses_a_category_it_does_not_know(): void
    {
        [$user, $vault, $contact] = $this->pamWithAReminder();

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/reminder-occurrences?category=postponed");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['category']);
    }

    /** @return array{0: User, 1: Vault, 2: Contact, 3: Reminder} */
    private function pamWithAReminder(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Pam']);
        $reminder = Reminder::factory()->manual($contact, now()->addWeek()->toDateString())->create(['timezone' => 'UTC']);

        return [$user, $vault, $contact, $reminder];
    }
}
