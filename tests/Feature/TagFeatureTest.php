<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Vault;
use App\Services\Tags\TagCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TagFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_tag(): void
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);

        $response = $this->postJson('/api/tags', [
            'name' => 'Client',
            'tag_category' => 'work',
            'color' => '#ff0000',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Client')
            ->assertJsonPath('data.tag_category', 'work')
            ->assertJsonPath('data.color', '#ff0000');

        $this->assertDatabaseHas('tags', [
            'vault_id' => $vault->id,
            'name' => 'Client',
            'slug' => 'client',
            'tag_category' => 'work',
            'color' => '#ff0000',
        ]);
    }

    public function test_can_list_tags(): void
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_VIEW);

        Tag::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'Test Tag',
        ]);

        $response = $this->getJson('/api/tags');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonPath('data.0.name', 'Test Tag');
    }

    public function test_can_update_tag(): void
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);

        $tag = Tag::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'Old Name',
        ]);

        $response = $this->putJson('/api/tags/'.$tag->id, [
            'name' => 'New Name',
            'color' => '#00ff00',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.color', '#00ff00');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'New Name',
            'slug' => 'new-name',
            'color' => '#00ff00',
        ]);
    }

    public function test_can_delete_tag(): void
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);

        $tag = Tag::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $response = $this->deleteJson('/api/tags/'.$tag->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_viewer_cannot_create_tag(): void
    {
        $user = $this->createUser();
        $this->createVaultUser($user, Vault::PERMISSION_VIEW);

        $response = $this->postJson('/api/tags', [
            'name' => 'Client',
        ]);

        $response->assertStatus(403);
    }

    public function test_tag_list_cache_is_invalidated_on_create(): void
    {
        $user = $this->createUser();
        $this->createVaultUser($user, Vault::PERMISSION_EDIT);

        $cache = app(TagCacheService::class);
        Cache::store('array')->put($cache->key(), collect(['stale']), 600);

        $this->postJson('/api/tags', [
            'name' => 'Fresh Tag',
        ])->assertStatus(201);

        $this->assertFalse(Cache::store('array')->has($cache->key()));
    }
}
