<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Group;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupReminderOccurrenceControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => ['reminder', 'name', 'group' => ['id', 'name'], 'date', 'scheduled_for', 'category', 'answer', 'answered_at', 'cancelled_reason'],
        'links' => ['reminder'],
    ];

    #[Test]
    public function it_lists_the_answered_dates_in_the_history(): void
    {
        [$user, $vault, $group, $reminder] = $this->finerThingsClubWithAReminder();
        ReminderOccurrence::factory()->past()->create(['reminder_id' => $reminder->id]);
        $answered = ReminderOccurrence::factory()->daysFromNow(-40)->forgotten()->create(['reminder_id' => $reminder->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/reminder-occurrences?category=history");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $answered->uuid);
        $response->assertJsonPath('data.0.attributes.answer', 'forgotten');
        $response->assertJsonPath('data.0.links.reminder', route('api.v1.groups.reminders.show', [$vault, $group->uuid, $reminder]));
    }

    #[Test]
    public function it_refuses_a_category_it_does_not_know(): void
    {
        [$user, $vault, $group] = $this->finerThingsClubWithAReminder();

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/reminder-occurrences?category=postponed");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['category']);
    }

    #[Test]
    public function it_does_not_find_a_group_of_another_account(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/reminder-occurrences")->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_group_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $otherVault->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/reminder-occurrences")
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Group, 3: Reminder} */
    private function finerThingsClubWithAReminder(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);
        $reminder = Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create(['timezone' => 'UTC']);

        return [$user, $vault, $group, $reminder];
    }
}
