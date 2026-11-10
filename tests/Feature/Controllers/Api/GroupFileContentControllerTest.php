<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Group;
use App\Models\GroupFile;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupFileContentControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_streams_the_file_with_its_original_name_and_type(): void
    {
        Storage::fake('local');
        [$user, $vault, $group] = $this->finerThingsClub();
        $attachment = GroupFile::factory()->create(['group_id' => $group->id]);
        $attachment->file->update(['original_name' => 'reading-list.pdf', 'mime_type' => 'application/pdf']);
        Storage::disk('local')->put($attachment->file->path, 'The Remains of the Day');

        Sanctum::actingAs($user);

        $response = $this->get("/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/files/{$attachment->uuid}/content");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('reading-list.pdf', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame('The Remains of the Day', $response->streamedContent());
    }

    #[Test]
    public function it_returns_not_found_when_the_bytes_are_gone(): void
    {
        Storage::fake('local');
        [$user, $vault, $group] = $this->finerThingsClub();
        $attachment = GroupFile::factory()->create(['group_id' => $group->id]);

        Sanctum::actingAs($user);

        $this->get("/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/files/{$attachment->uuid}/content")
            ->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_file_kept_with_another_group(): void
    {
        [$user, $vault, $group] = $this->finerThingsClub();
        $attachment = GroupFile::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $this->get("/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/files/{$attachment->uuid}/content")
            ->assertNotFound();
    }

    #[Test]
    public function it_returns_not_found_for_a_file_of_another_account(): void
    {
        [$user] = $this->finerThingsClub();
        $attachment = GroupFile::factory()->create();

        Sanctum::actingAs($user);

        $this->get("/api/v1/vaults/{$attachment->group->vault->uuid}/groups/{$attachment->group->uuid}/files/{$attachment->uuid}/content")
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_group_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $otherVault->id]);
        $attachment = GroupFile::factory()->create(['group_id' => $group->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/groups/{$group->uuid}/files/{$attachment->uuid}/content")
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
