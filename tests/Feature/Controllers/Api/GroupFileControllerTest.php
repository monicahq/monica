<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Group;
use App\Models\GroupFile;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupFileControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'group' => ['id', 'name'],
            'name',
            'mime_type',
            'size_in_bytes',
            'created_at',
        ],
        'links' => ['self', 'content'],
    ];

    #[Test]
    public function it_lists_the_files_of_the_group_newest_first(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $older = GroupFile::factory()->create(['group_id' => $group->id, 'created_at' => '2026-10-01 10:00:00']);
        $newer = GroupFile::factory()->create(['group_id' => $group->id, 'created_at' => '2026-11-01 10:00:00']);
        GroupFile::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/files");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $newer->uuid);
        $response->assertJsonPath('data.1.id', $older->uuid);
    }

    #[Test]
    public function it_returns_not_found_for_the_files_of_a_group_of_another_account(): void
    {
        [$user] = $this->finerThingsClub();
        $attachment = GroupFile::factory()->create();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$attachment->group->vault->uuid}/groups/{$attachment->group->uuid}/files")
            ->assertNotFound();
    }

    #[Test]
    public function it_shows_a_file(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $attachment = GroupFile::factory()->create(['group_id' => $group->id]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/files/{$attachment->uuid}");

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.name', $attachment->file->original_name);
        $response->assertJsonPath('data.attributes.group.name', 'Finer things club');
    }

    #[Test]
    public function it_returns_not_found_for_a_file_kept_with_another_group(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $attachment = GroupFile::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/files/{$attachment->uuid}")
            ->assertNotFound();
    }

    #[Test]
    public function it_keeps_an_uploaded_file_with_the_group(): void
    {
        Queue::fake();
        Storage::fake('local');
        [$user, $vault, $group] = $this->finerThingsClub();

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/files", [
            'file' => UploadedFile::fake()->createWithContent('reading-list.txt', 'The Remains of the Day'),
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.name', 'reading-list.txt');
        $this->assertDatabaseHas('group_files', ['group_id' => $group->id, 'account_id' => $user->account_id]);
    }

    #[Test]
    public function it_refuses_an_upload_the_account_has_no_room_for(): void
    {
        Storage::fake('local');
        [$user, $vault, $group] = $this->finerThingsClub();
        $user->account->update(['storage_limit_in_bytes' => 1024, 'storage_used_in_bytes' => 512]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/files", [
            'file' => UploadedFile::fake()->create('scan.pdf', 4),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('group_files', 0);
    }

    #[Test]
    public function it_refuses_an_upload_without_a_file(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/files", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    #[Test]
    public function it_stops_keeping_a_file_with_the_group(): void
    {
        Queue::fake();
        [$user, $vault, $group] = $this->finerThingsClub();
        $attachment = GroupFile::factory()->create(['group_id' => $group->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/files/{$attachment->uuid}")
            ->assertNoContent();

        $this->assertModelMissing($attachment);
    }

    #[Test]
    public function it_does_not_stop_keeping_a_file_through_another_group(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $attachment = GroupFile::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/files/{$attachment->uuid}")
            ->assertNotFound();

        $this->assertModelExists($attachment);
    }

    #[Test]
    public function it_answers_not_found_when_file_upload_is_not_offered(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $user->account->features()->deactivate('file-upload');
        Feature::flushCache();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/files")
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

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/files")
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
