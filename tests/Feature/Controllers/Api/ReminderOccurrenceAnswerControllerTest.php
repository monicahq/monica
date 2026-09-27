<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderOccurrenceAnswerControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_the_answer(): void
    {
        Queue::fake();
        [$user, $vault, $contact, $reminder] = $this->pamWithAReminder();
        $occurrence = ReminderOccurrence::factory()->past()->create(['reminder_id' => $reminder->id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/reminder-occurrences/{$occurrence->uuid}/answer", ['answer' => 'remembered']);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.answer', 'remembered');
        $this->assertDatabaseHas('reminder_occurrences', ['id' => $occurrence->id, 'answer' => 'remembered']);
    }

    #[Test]
    public function it_refuses_a_second_answer(): void
    {
        [$user, $vault, $contact, $reminder] = $this->pamWithAReminder();
        $occurrence = ReminderOccurrence::factory()->past()->remembered()->create(['reminder_id' => $reminder->id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/reminder-occurrences/{$occurrence->uuid}/answer", ['answer' => 'forgotten']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['answer']);
        $this->assertDatabaseHas('reminder_occurrences', ['id' => $occurrence->id, 'answer' => 'remembered']);
    }

    #[Test]
    public function it_does_not_find_a_date_of_another_contact(): void
    {
        [$user, $vault, $contact] = $this->pamWithAReminder();
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Jim']);
        $jimsReminder = Reminder::factory()->manual($jim, now()->addWeek()->toDateString())->create();
        $occurrence = ReminderOccurrence::factory()->past()->create(['reminder_id' => $jimsReminder->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/reminder-occurrences/{$occurrence->uuid}/answer", ['answer' => 'remembered'])
            ->assertNotFound();
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
