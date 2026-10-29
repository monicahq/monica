<?php

namespace Tests\Feature\Api;

use App\Models\Contact;
use App\Models\Label;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\ApiTestCase;

class TagManagementTest extends ApiTestCase
{
    use RefreshDatabase;

    private User $user;
    private Vault $vault;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser(['read', 'write']);
        $this->vault = $this->createVaultUser($this->user, Vault::PERMISSION_EDIT);
    }

    #[Test]
    public function it_can_list_all_tags(): void
    {
        // Create some tags
        Label::factory()->count(3)->create([
            'vault_id' => $this->vault->id,
        ]);

        $response = $this->getJson("/api/vaults/{$this->vault->id}/tags");

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_can_create_a_new_tag(): void
    {
        $response = $this->postJson("/api/vaults/{$this->vault->id}/tags", [
            'name' => 'Colleague',
            'category' => 'Work',
            'color' => '#FF5733',
            'description' => 'Work colleagues',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'name' => 'Colleague',
            'category' => 'Work',
            'color' => '#FF5733',
            'description' => 'Work colleagues',
        ]);

        $this->assertDatabaseHas('labels', [
            'vault_id' => $this->vault->id,
            'name' => 'Colleague',
            'category' => 'Work',
        ]);
    }

    #[Test]
    public function it_can_verify_created_tag_appears_in_list(): void
    {
        // Create a tag
        $response = $this->postJson("/api/vaults/{$this->vault->id}/tags", [
            'name' => 'Family',
            'category' => 'Personal',
        ]);

        $response->assertStatus(201);
        $tagId = $response->json('data.id');

        // List tags and verify it appears
        $listResponse = $this->getJson("/api/vaults/{$this->vault->id}/tags");

        $listResponse->assertStatus(200);
        $listResponse->assertJsonFragment([
            'id' => $tagId,
            'name' => 'Family',
            'category' => 'Personal',
        ]);
    }

    #[Test]
    public function it_can_update_a_tag(): void
    {
        $tag = Label::factory()->create([
            'vault_id' => $this->vault->id,
            'name' => 'Old Name',
        ]);

        $response = $this->putJson("/api/vaults/{$this->vault->id}/tags/{$tag->id}", [
            'name' => 'New Name',
            'category' => 'Updated Category',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'New Name',
            'category' => 'Updated Category',
        ]);

        $this->assertDatabaseHas('labels', [
            'id' => $tag->id,
            'name' => 'New Name',
            'category' => 'Updated Category',
        ]);
    }

    #[Test]
    public function it_can_delete_a_tag(): void
    {
        $tag = Label::factory()->create([
            'vault_id' => $this->vault->id,
        ]);

        $response = $this->deleteJson("/api/vaults/{$this->vault->id}/tags/{$tag->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('labels', [
            'id' => $tag->id,
        ]);
    }

    #[Test]
    public function it_can_attach_tags_to_a_contact(): void
    {
        $contact = Contact::factory()->create([
            'vault_id' => $this->vault->id,
        ]);

        $tag1 = Label::factory()->create([
            'vault_id' => $this->vault->id,
            'name' => 'Friend',
        ]);

        $tag2 = Label::factory()->create([
            'vault_id' => $this->vault->id,
            'name' => 'Colleague',
        ]);

        $response = $this->postJson("/api/vaults/{$this->vault->id}/contacts/{$contact->id}/tags", [
            'tag_ids' => [$tag1->id, $tag2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => 'Tags attached successfully',
            'tag_ids' => [$tag1->id, $tag2->id],
        ]);

        $this->assertDatabaseHas('contact_label', [
            'contact_id' => $contact->id,
            'label_id' => $tag1->id,
        ]);

        $this->assertDatabaseHas('contact_label', [
            'contact_id' => $contact->id,
            'label_id' => $tag2->id,
        ]);
    }

    #[Test]
    public function it_can_filter_contacts_by_multiple_tags_with_and_logic(): void
    {
        $contact1 = Contact::factory()->create([
            'vault_id' => $this->vault->id,
            'listed' => true,
        ]);

        $contact2 = Contact::factory()->create([
            'vault_id' => $this->vault->id,
            'listed' => true,
        ]);

        $contact3 = Contact::factory()->create([
            'vault_id' => $this->vault->id,
            'listed' => true,
        ]);

        $tag1 = Label::factory()->create([
            'vault_id' => $this->vault->id,
            'name' => 'Friend',
        ]);

        $tag2 = Label::factory()->create([
            'vault_id' => $this->vault->id,
            'name' => 'Close',
        ]);

        // Attach tags to contacts
        $contact1->labels()->sync([$tag1->id, $tag2->id]); // Has both tags
        $contact2->labels()->sync([$tag1->id]); // Has only tag1
        $contact3->labels()->sync([$tag2->id]); // Has only tag2

        // Filter by both tags (AND logic)
        $response = $this->getJson("/api/contacts?vault_id={$this->vault->id}&tags[]={$tag1->id}&tags[]={$tag2->id}");

        $response->assertStatus(200);
        
        // Should only return contact1 (has both tags)
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'id' => $contact1->id,
        ]);

        // Verify contact2 and contact3 are not in the response
        $ids = $response->json('data.*.id');
        $this->assertNotContains($contact2->id, $ids);
        $this->assertNotContains($contact3->id, $ids);
    }

    #[Test]
    public function it_can_detach_a_tag_from_a_contact(): void
    {
        $contact = Contact::factory()->create([
            'vault_id' => $this->vault->id,
        ]);

        $tag = Label::factory()->create([
            'vault_id' => $this->vault->id,
        ]);

        $contact->labels()->sync([$tag->id]);

        $response = $this->deleteJson("/api/vaults/{$this->vault->id}/contacts/{$contact->id}/tags/{$tag->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('contact_label', [
            'contact_id' => $contact->id,
            'label_id' => $tag->id,
        ]);
    }

    #[Test]
    public function it_can_delete_a_tag_attached_to_contacts_and_detaches_from_all(): void
    {
        $contact1 = Contact::factory()->create([
            'vault_id' => $this->vault->id,
        ]);

        $contact2 = Contact::factory()->create([
            'vault_id' => $this->vault->id,
        ]);

        $tag = Label::factory()->create([
            'vault_id' => $this->vault->id,
            'name' => 'Deletable',
        ]);

        // Attach tag to both contacts
        $contact1->labels()->sync([$tag->id]);
        $contact2->labels()->sync([$tag->id]);

        $response = $this->deleteJson("/api/vaults/{$this->vault->id}/tags/{$tag->id}");

        $response->assertStatus(204);

        // Verify tag is deleted
        $this->assertDatabaseMissing('labels', [
            'id' => $tag->id,
        ]);

        // Verify detached from all contacts
        $this->assertDatabaseMissing('contact_label', [
            'label_id' => $tag->id,
        ]);
    }

    #[Test]
    public function it_invalidates_cache_on_tag_creation(): void
    {
        Cache::spy();

        $vaultId = $this->vault->id;
        $cacheKey = "tags:vault:{$vaultId}";

        // Verify cache is forgotten on tag creation
        $response = $this->postJson("/api/vaults/{$vaultId}/tags", [
            'name' => 'Test Tag',
        ]);

        $response->assertStatus(201);

        Cache::shouldHaveReceived('forget')->with($cacheKey);
    }

    #[Test]
    public function it_invalidates_cache_on_tag_update(): void
    {
        Cache::spy();

        $tag = Label::factory()->create([
            'vault_id' => $this->vault->id,
        ]);

        $vaultId = $this->vault->id;
        $cacheKey = "tags:vault:{$vaultId}";

        $response = $this->putJson("/api/vaults/{$vaultId}/tags/{$tag->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);

        Cache::shouldHaveReceived('forget')->with($cacheKey);
    }

    #[Test]
    public function it_invalidates_cache_on_tag_deletion(): void
    {
        Cache::spy();

        $tag = Label::factory()->create([
            'vault_id' => $this->vault->id,
        ]);

        $vaultId = $this->vault->id;
        $cacheKey = "tags:vault:{$vaultId}";

        $response = $this->deleteJson("/api/vaults/{$vaultId}/tags/{$tag->id}");

        $response->assertStatus(204);

        Cache::shouldHaveReceived('forget')->with($cacheKey);
    }

    #[Test]
    public function it_invalidates_cache_on_tag_attach(): void
    {
        Cache::spy();

        $contact = Contact::factory()->create([
            'vault_id' => $this->vault->id,
        ]);

        $tag = Label::factory()->create([
            'vault_id' => $this->vault->id,
        ]);

        $vaultId = $this->vault->id;
        $cacheKey = "tags:vault:{$vaultId}";

        $response = $this->postJson("/api/vaults/{$vaultId}/contacts/{$contact->id}/tags", [
            'tag_ids' => [$tag->id],
        ]);

        $response->assertStatus(200);

        Cache::shouldHaveReceived('forget')->with($cacheKey);
    }

    #[Test]
    public function it_invalidates_cache_on_tag_detach(): void
    {
        Cache::spy();

        $contact = Contact::factory()->create([
            'vault_id' => $this->vault->id,
        ]);

        $tag = Label::factory()->create([
            'vault_id' => $this->vault->id,
        ]);

        $contact->labels()->sync([$tag->id]);

        $vaultId = $this->vault->id;
        $cacheKey = "tags:vault:{$vaultId}";

        $response = $this->deleteJson("/api/vaults/{$vaultId}/contacts/{$contact->id}/tags/{$tag->id}");

        $response->assertStatus(204);

        Cache::shouldHaveReceived('forget')->with($cacheKey);
    }

    #[Test]
    public function it_returns_tags_with_usage_count(): void
    {
        $tag1 = Label::factory()->create([
            'vault_id' => $this->vault->id,
            'name' => 'Popular Tag',
        ]);

        $tag2 = Label::factory()->create([
            'vault_id' => $this->vault->id,
            'name' => 'Unused Tag',
        ]);

        // Create contacts and attach to tag1
        Contact::factory()->count(3)->create([
            'vault_id' => $this->vault->id,
        ])->each(function (Contact $contact) use ($tag1) {
            $contact->labels()->sync([$tag1->id]);
        });

        $response = $this->getJson("/api/vaults/{$this->vault->id}/tags");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');

        // Verify usage counts are included
        $tags = $response->json('data');
        $this->assertEquals(3, collect($tags)->firstWhere('name', 'Popular Tag')['contacts_count']);
        $this->assertEquals(0, collect($tags)->firstWhere('name', 'Unused Tag')['contacts_count']);
    }
}
