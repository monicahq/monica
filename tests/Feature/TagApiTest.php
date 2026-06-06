<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TagApiTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function can_create_a_tag()
    {
        $user = $this->createUser();
        $account = $user->account;
        $vault = $this->createVaultUser($user);

        $response = $this->post(
            "/api/vaults/{$vault->id}/tags",
            [
                'name' => 'Colleague',
                'tag_category' => 'Work',
                'color' => '#FF5733',
            ]
        );

        $response->assertCreated();
        $response->assertJsonStructure([
            'id',
            'name',
            'slug',
            'tag_category',
            'color',
            'usage_count',
            'created_at',
            'updated_at',
            'links' => ['self'],
        ]);

        $this->assertDatabaseHas('tags', [
            'vault_id' => $vault->id,
            'name' => 'Colleague',
            'tag_category' => 'Work',
            'color' => '#FF5733',
        ]);
    }

    #[Test]
    public function can_list_tags_with_usage_count()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        // Create tags
        $tag1 = Tag::factory()->create(['vault_id' => $vault->id, 'name' => 'Tag 1']);
        $tag2 = Tag::factory()->create(['vault_id' => $vault->id, 'name' => 'Tag 2']);

        // Create contacts and attach tags
        $contact1 = Contact::factory()->create(['vault_id' => $vault->id]);
        $contact1->tags()->attach([$tag1->id, $tag2->id]);

        $contact2 = Contact::factory()->create(['vault_id' => $vault->id]);
        $contact2->tags()->attach([$tag1->id]);

        $response = $this->get("/api/vaults/{$vault->id}/tags");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'id',
                    'name',
                    'usage_count',
                ],
            ],
        ]);

        // Verify usage counts
        $data = $response->json('data');
        $this->assertEquals(2, count($data));

        $tag1Data = collect($data)->firstWhere('id', $tag1->id);
        $tag2Data = collect($data)->firstWhere('id', $tag2->id);

        $this->assertEquals(2, $tag1Data['usage_count']);
        $this->assertEquals(1, $tag2Data['usage_count']);
    }

    #[Test]
    public function tag_list_is_cached()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $tag = Tag::factory()->create(['vault_id' => $vault->id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $contact->tags()->attach($tag->id);

        // First request — should hit DB and cache result
        $response1 = $this->get("/api/vaults/{$vault->id}/tags");
        $response1->assertOk();

        // Verify cache was set
        $cacheKey = "tags:vault:{$vault->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Second request — should hit cache
        $response2 = $this->get("/api/vaults/{$vault->id}/tags");
        $response2->assertOk();

        // Responses should be identical
        $this->assertEquals($response1->json(), $response2->json());
    }

    #[Test]
    public function cache_is_invalidated_when_tag_is_created()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $tag = Tag::factory()->create(['vault_id' => $vault->id]);

        // Prime the cache
        $this->get("/api/vaults/{$vault->id}/tags");
        $cacheKey = "tags:vault:{$vault->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Create a new tag
        $this->post("/api/vaults/{$vault->id}/tags", [
            'name' => 'New Tag',
        ]);

        // Verify cache was invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function cache_is_invalidated_when_tag_is_updated()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $tag = Tag::factory()->create(['vault_id' => $vault->id]);

        // Prime the cache
        $this->get("/api/vaults/{$vault->id}/tags");
        $cacheKey = "tags:vault:{$vault->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Update the tag
        $this->put("/api/vaults/{$vault->id}/tags/{$tag->id}", [
            'name' => 'Updated Tag',
        ]);

        // Verify cache was invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function cache_is_invalidated_when_tag_is_deleted()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $tag = Tag::factory()->create(['vault_id' => $vault->id]);

        // Prime the cache
        $this->get("/api/vaults/{$vault->id}/tags");
        $cacheKey = "tags:vault:{$vault->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Delete the tag
        $this->delete("/api/vaults/{$vault->id}/tags/{$tag->id}");

        // Verify cache was invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function cache_is_invalidated_when_tag_is_attached_to_contact()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $tag = Tag::factory()->create(['vault_id' => $vault->id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        // Prime the cache
        $this->get("/api/vaults/{$vault->id}/tags");
        $cacheKey = "tags:vault:{$vault->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Attach tag to contact
        $this->post("/api/vaults/{$vault->id}/contacts/{$contact->id}/tags", [
            'tag_ids' => [$tag->id],
        ]);

        // Verify cache was invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function cache_is_invalidated_when_tag_is_detached_from_contact()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $tag = Tag::factory()->create(['vault_id' => $vault->id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $contact->tags()->attach($tag->id);

        // Prime the cache
        $this->get("/api/vaults/{$vault->id}/tags");
        $cacheKey = "tags:vault:{$vault->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Detach tag from contact
        $this->delete("/api/vaults/{$vault->id}/contacts/{$contact->id}/tags/{$tag->id}");

        // Verify cache was invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function can_attach_tags_to_contact()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $tag1 = Tag::factory()->create(['vault_id' => $vault->id]);
        $tag2 = Tag::factory()->create(['vault_id' => $vault->id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $response = $this->post(
            "/api/vaults/{$vault->id}/contacts/{$contact->id}/tags",
            [
                'tag_ids' => [$tag1->id, $tag2->id],
            ]
        );

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));

        // Verify tags are attached
        $this->assertTrue($contact->fresh()->tags->contains($tag1->id));
        $this->assertTrue($contact->fresh()->tags->contains($tag2->id));
    }

    #[Test]
    public function can_detach_tag_from_contact()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $tag1 = Tag::factory()->create(['vault_id' => $vault->id]);
        $tag2 = Tag::factory()->create(['vault_id' => $vault->id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $contact->tags()->attach([$tag1->id, $tag2->id]);

        $response = $this->delete(
            "/api/vaults/{$vault->id}/contacts/{$contact->id}/tags/{$tag1->id}"
        );

        $response->assertOk();

        // Verify tag is detached
        $this->assertFalse($contact->fresh()->tags->contains($tag1->id));
        $this->assertTrue($contact->fresh()->tags->contains($tag2->id));
    }

    #[Test]
    public function can_filter_contacts_by_single_tag()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $tag = Tag::factory()->create(['vault_id' => $vault->id]);
        $contact1 = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Alice']);
        $contact2 = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Bob']);

        $contact1->tags()->attach($tag->id);

        $response = $this->get("/api/vaults/{$vault->id}/contacts?tags[]={$tag->id}");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Alice', $data[0]['first_name']);
    }

    #[Test]
    public function can_filter_contacts_by_multiple_tags_with_and_logic()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $tag1 = Tag::factory()->create(['vault_id' => $vault->id]);
        $tag2 = Tag::factory()->create(['vault_id' => $vault->id]);

        // Contact with both tags
        $contact1 = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Alice']);
        $contact1->tags()->attach([$tag1->id, $tag2->id]);

        // Contact with only tag1
        $contact2 = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Bob']);
        $contact2->tags()->attach($tag1->id);

        // Contact with only tag2
        $contact3 = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Charlie']);
        $contact3->tags()->attach($tag2->id);

        // Filter by both tags (AND logic) — should only return contact1
        $response = $this->get(
            "/api/vaults/{$vault->id}/contacts?tags[]={$tag1->id}&tags[]={$tag2->id}"
        );

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Alice', $data[0]['first_name']);
    }

    #[Test]
    public function filtering_contacts_by_tags_works_with_pagination()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $tag = Tag::factory()->create(['vault_id' => $vault->id]);

        // Create multiple contacts with the tag
        for ($i = 0; $i < 15; $i++) {
            $contact = Contact::factory()->create([
                'vault_id' => $vault->id,
                'first_name' => "Contact {$i}",
            ]);
            $contact->tags()->attach($tag->id);
        }

        $response = $this->get("/api/vaults/{$vault->id}/contacts?tags[]={$tag->id}&limit=10");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(10, $data);

        // Verify pagination meta
        $this->assertEquals(1, $response->json('meta.current_page'));
        $this->assertEquals(15, $response->json('meta.total'));
    }

    #[Test]
    public function can_retrieve_single_tag()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $tag = Tag::factory()->create(['vault_id' => $vault->id, 'name' => 'Important']);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $contact->tags()->attach($tag->id);

        $response = $this->get("/api/vaults/{$vault->id}/tags/{$tag->id}");

        $response->assertOk();
        $this->assertEquals('Important', $response->json('name'));
        $this->assertEquals(1, $response->json('usage_count'));
    }

    #[Test]
    public function can_update_tag()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $tag = Tag::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'Old Name',
            'tag_category' => 'Old Category',
            'color' => '#000000',
        ]);

        $response = $this->put("/api/vaults/{$vault->id}/tags/{$tag->id}", [
            'name' => 'New Name',
            'tag_category' => 'New Category',
            'color' => '#FFFFFF',
        ]);

        $response->assertOk();
        $this->assertEquals('New Name', $response->json('name'));
        $this->assertEquals('New Category', $response->json('tag_category'));
        $this->assertEquals('#FFFFFF', $response->json('color'));

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'New Name',
        ]);
    }

    #[Test]
    public function can_delete_tag_and_it_detaches_from_contacts()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $tag = Tag::factory()->create(['vault_id' => $vault->id]);
        $contact1 = Contact::factory()->create(['vault_id' => $vault->id]);
        $contact2 = Contact::factory()->create(['vault_id' => $vault->id]);

        $contact1->tags()->attach($tag->id);
        $contact2->tags()->attach($tag->id);

        $response = $this->delete("/api/vaults/{$vault->id}/tags/{$tag->id}");

        $response->assertOk();

        // Verify tag is deleted
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);

        // Verify tags are detached from contacts
        $this->assertCount(0, $contact1->fresh()->tags);
        $this->assertCount(0, $contact2->fresh()->tags);
    }

    #[Test]
    public function requires_read_ability_to_list_tags()
    {
        $user = User::factory()->create();
        $this->actingAs($user, ['write']);
        $vault = $this->createVaultUser($user);

        $response = $this->get("/api/vaults/{$vault->id}/tags");

        $response->assertUnauthorized();
    }

    #[Test]
    public function requires_write_ability_to_create_tag()
    {
        $user = User::factory()->create();
        $this->actingAs($user, ['read']);
        $vault = $this->createVaultUser($user);

        $response = $this->post("/api/vaults/{$vault->id}/tags", [
            'name' => 'Test Tag',
        ]);

        $response->assertUnauthorized();
    }
}
