<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Group;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupReminderControllerTest extends TestCase
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
            'group' => ['id', 'name'],
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
    public function it_lists_the_reminders_about_a_group(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create(['name' => 'Book the room']);
        Reminder::factory()->manual(Group::factory()->create(['vault_id' => $vault->id]), now()->addWeek()->toDateString())->create();

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/reminders");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.name', 'Book the room');
        $response->assertJsonPath('data.0.attributes.group.id', $group->uuid);
        $response->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function it_does_not_find_a_group_of_another_account(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$group->vault->uuid}/groups/{$group->uuid}/reminders")->assertNotFound();
    }

    #[Test]
    public function it_writes_a_reminder_about_a_group(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/reminders", [
            'name' => 'Book the room',
            'starts_on' => now()->addWeek()->toDateString(),
            'cadence' => 'monthly',
            'time' => '09:00',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.group.name', 'Finer things club');
        $this->assertDatabaseHas('reminders', [
            'remindable_type' => $group->getMorphClass(),
            'remindable_id' => $group->id,
            'name' => 'Book the room',
            'source' => 'manual',
        ]);
    }

    #[Test]
    public function it_refuses_a_reminder_that_starts_in_the_past(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/reminders", [
            'name' => 'Book the room',
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
        [$user, $vault, $group] = $this->finerThingsClub();
        $reminder = Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create();

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/reminders/{$reminder->uuid}");

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.id', $reminder->uuid);
        $response->assertJsonPath('data.links.self', route('api.v1.groups.reminders.show', [$vault, $group->uuid, $reminder]));
    }

    #[Test]
    public function it_does_not_find_a_reminder_about_another_group_of_the_vault(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $reminder = Reminder::factory()->manual(Group::factory()->create(['vault_id' => $vault->id]), now()->addWeek()->toDateString())->create();

        Sanctum::actingAs($user);

        $url = "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/reminders/{$reminder->uuid}";

        $this->json('GET', $url)->assertNotFound();
        $this->json('DELETE', $url)->assertNotFound();
        $this->assertModelExists($reminder);
    }

    #[Test]
    public function it_changes_a_reminder(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $reminder = Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create(['frequency_interval' => 3]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/reminders/{$reminder->uuid}", [
            'name' => 'Order the brie',
            'cadence' => 'yearly',
            'time' => '10:00',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', 'Order the brie');
        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'name' => 'Order the brie', 'cadence' => 'yearly', 'frequency_interval' => 3]);
    }

    #[Test]
    public function it_deletes_a_reminder(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $reminder = Reminder::factory()->manual($group, now()->addWeek()->toDateString())->create();

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/reminders/{$reminder->uuid}")->assertNoContent();

        $this->assertSoftDeleted($reminder);
    }

    #[Test]
    public function it_does_not_find_a_group_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $otherVault->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/reminders")
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Group} */
    private function finerThingsClub(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club'])];
    }
}
