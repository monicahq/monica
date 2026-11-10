<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Group;
use App\Models\GroupNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupNoteControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'group' => ['id', 'name'],
            'body',
            'created_at',
            'updated_at',
        ],
        'links' => ['self'],
    ];

    #[Test]
    public function it_lists_the_notes_of_the_group_newest_first(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $older = GroupNote::factory()->create(['group_id' => $group->id, 'created_at' => '2026-10-01 10:00:00']);
        $newer = GroupNote::factory()->create(['group_id' => $group->id, 'created_at' => '2026-11-01 10:00:00']);
        GroupNote::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/notes");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $newer->uuid);
        $response->assertJsonPath('data.1.id', $older->uuid);
    }

    #[Test]
    public function it_shows_a_note(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $note = GroupNote::factory()->create(['group_id' => $group->id, 'body' => 'No paper plates.']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/notes/{$note->uuid}");

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.body', 'No paper plates.');
    }

    #[Test]
    public function it_returns_not_found_for_a_note_of_another_account(): void
    {
        [$user] = $this->finerThingsClub();
        $note = GroupNote::factory()->create();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$note->vault->uuid}/groups/{$note->group->uuid}/notes/{$note->uuid}")
            ->assertNotFound();
    }

    #[Test]
    public function it_writes_a_note(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->finerThingsClub();

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/notes", ['body' => 'Oscar brings the cheese.']);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.body', 'Oscar brings the cheese.');
    }

    #[Test]
    public function it_validates_the_body_when_writing_a_note(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/notes", ['body' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    #[Test]
    public function it_refuses_a_note_that_is_only_markup(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/notes", ['body' => '<b></b>'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    #[Test]
    public function it_changes_a_note(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->finerThingsClub();
        $note = GroupNote::factory()->create(['group_id' => $group->id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/notes/{$note->uuid}", ['body' => 'Paper plates are fine.']);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.body', 'Paper plates are fine.');
    }

    #[Test]
    public function it_deletes_a_note(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->finerThingsClub();
        $note = GroupNote::factory()->create(['group_id' => $group->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/notes/{$note->uuid}")
            ->assertNoContent();

        $this->assertSoftDeleted($note);
    }

    #[Test]
    public function it_does_not_find_a_note_through_another_group(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $note = GroupNote::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/notes/{$note->uuid}")
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_group_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $otherVault->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/notes")
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
