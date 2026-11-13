<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\DefaultFieldEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultReminderOccurrenceControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'data' => [
            '*' => [
                'type',
                'id',
                'attributes' => ['reminder', 'name', 'group', 'date', 'scheduled_for', 'category', 'answer', 'answered_at', 'cancelled_reason', 'subject' => ['type', 'id', 'name'], 'age'],
                'links' => ['reminder', 'answer'],
            ],
        ],
        'meta' => ['page', 'per_page', 'total'],
    ];

    #[Test]
    public function it_lists_the_dates_of_the_vault(): void
    {
        [$user, $vault, $pam] = $this->pamWithAVault();
        $reminder = Reminder::factory()->manual($pam, now()->subDays(3)->toDateString())->create(['timezone' => 'UTC']);
        $overdue = ReminderOccurrence::factory()->past()->create(['reminder_id' => $reminder->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/reminder-occurrences");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.0.id', $overdue->uuid);
        $response->assertJsonPath('data.0.attributes.category', 'overdue');
        $response->assertJsonPath('data.0.attributes.subject.name', 'Pam Beesly');
        $response->assertJsonPath('data.0.links.answer', route('api.v1.contacts.reminderOccurrences.answer.update', [$vault, $pam, $overdue->uuid]));
    }

    #[Test]
    public function it_lists_a_birthday_nobody_asked_to_be_reminded_of(): void
    {
        [$user, $vault, $pam] = $this->pamWithAVault();
        $field = Field::factory()->recurringDate()->create(['key' => DefaultFieldEnum::Birthday->value]);
        FieldValue::factory()->recurringDate(now()->addDays(2)->subYears(30)->toDateString())->create([
            'valuable_id' => $pam->id,
            'field_id' => $field->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/reminder-occurrences?kind=birthdays");

        $response->assertOk();
        $response->assertJsonPath('data.0.type', 'birthday');
        $response->assertJsonPath('data.0.id', null);
        $response->assertJsonPath('data.0.attributes.age', 30);
        $response->assertJsonPath('data.0.attributes.category', 'upcoming');
        $response->assertJsonPath('data.0.attributes.subject.id', $pam->uuid);
    }

    #[Test]
    public function it_refuses_what_it_does_not_know(): void
    {
        [$user, $vault] = $this->pamWithAVault();

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/reminder-occurrences?category=postponed&kind=anniversaries&days=366");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['category', 'kind', 'days']);
    }

    #[Test]
    public function it_does_not_find_the_vault_of_another_account(): void
    {
        [, $vault] = $this->pamWithAVault();

        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/reminder-occurrences")->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Contact} */
    private function pamWithAVault(): array
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $pam = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);

        return [$user, $vault, $pam];
    }
}
