<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Group;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupReminderOccurrenceAnswerControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_the_answer(): void
    {
        Queue::fake();
        [$user, $vault, $group, $reminder] = $this->finerThingsClubWithAReminder();
        $occurrence = ReminderOccurrence::factory()->past()->create(['reminder_id' => $reminder->id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/reminder-occurrences/{$occurrence->uuid}/answer", ['answer' => 'remembered']);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.answer', 'remembered');
        $response->assertJsonPath('data.attributes.group.id', $group->uuid);
        $this->assertDatabaseHas('reminder_occurrences', ['id' => $occurrence->id, 'answer' => 'remembered']);
    }

    #[Test]
    public function it_refuses_a_second_answer(): void
    {
        [$user, $vault, $group, $reminder] = $this->finerThingsClubWithAReminder();
        $occurrence = ReminderOccurrence::factory()->past()->remembered()->create(['reminder_id' => $reminder->id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/reminder-occurrences/{$occurrence->uuid}/answer", ['answer' => 'forgotten']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['answer']);
        $this->assertDatabaseHas('reminder_occurrences', ['id' => $occurrence->id, 'answer' => 'remembered']);
    }

    #[Test]
    public function it_does_not_find_a_date_of_another_group(): void
    {
        [$user, $vault, $group] = $this->finerThingsClubWithAReminder();
        $otherReminder = Reminder::factory()->manual(Group::factory()->create(['vault_id' => $vault->id]), now()->addWeek()->toDateString())->create();
        $occurrence = ReminderOccurrence::factory()->past()->create(['reminder_id' => $otherReminder->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/reminder-occurrences/{$occurrence->uuid}/answer", ['answer' => 'remembered'])
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_group_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $otherVault->id]);
        $reminder = Reminder::factory()->manual($group, '2027-05-20')->create();
        $occurrence = ReminderOccurrence::factory()->past()->create(['reminder_id' => $reminder->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/reminder-occurrences/{$occurrence->uuid}/answer", ['answer' => 'remembered'])
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
