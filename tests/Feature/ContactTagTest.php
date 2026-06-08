<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Tag;
use App\Models\Vault;
use App\Services\Tags\TagCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ContactTagTest extends TestCase
{
    use RefreshDatabase;

    private function contactTagsRoute(Contact $contact, ?int $tagId = null): string
    {
        $path = '/api/contacts/'.$contact->id.'/tags';

        if ($tagId !== null) {
            $path .= '/'.$tagId;
        }

        return $path;
    }

    public function test_can_attach_tags_to_contact(): void
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'listed' => true,
        ]);

        $tag = Tag::factory()->create(['vault_id' => $vault->id]);

        $response = $this->postJson($this->contactTagsRoute($contact), [
            'tag_ids' => [$tag->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_can_detach_tag_from_contact(): void
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'listed' => true,
        ]);

        $tag = Tag::factory()->create(['vault_id' => $vault->id]);
        $contact->tags()->attach($tag->id);

        $response = $this->deleteJson($this->contactTagsRoute($contact, $tag->id));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_cannot_attach_tag_from_another_vault(): void
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_EDIT);
        $otherVault = $this->createVault($user->account);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'listed' => true,
        ]);

        $foreignTag = Tag::factory()->create(['vault_id' => $otherVault->id]);

        $response = $this->postJson($this->contactTagsRoute($contact), [
            'tag_ids' => [$foreignTag->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tag_ids']);
    }

    public function test_attach_invalidates_tag_list_cache(): void
    {
        $user = $this->createUser();
        $this->createVaultUser($user, Vault::PERMISSION_EDIT);

        $contact = Contact::factory()->create([
            'vault_id' => $user->vault_id,
            'listed' => true,
        ]);

        $tag = Tag::factory()->create(['vault_id' => $user->vault_id]);

        $cache = app(TagCacheService::class);
        Cache::store('array')->put($cache->key(), collect(['stale']), 600);

        $this->postJson($this->contactTagsRoute($contact), [
            'tag_ids' => [$tag->id],
        ])->assertStatus(200);

        $this->assertFalse(Cache::store('array')->has($cache->key()));
    }

    public function test_filters_contacts_with_and_tag_logic(): void
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_VIEW);

        $tagA = Tag::factory()->create(['vault_id' => $vault->id, 'name' => 'A']);
        $tagB = Tag::factory()->create(['vault_id' => $vault->id, 'name' => 'B']);
        $tagC = Tag::factory()->create(['vault_id' => $vault->id, 'name' => 'C']);

        $contactAB = Contact::factory()->create(['vault_id' => $vault->id, 'listed' => true]);
        $contactA = Contact::factory()->create(['vault_id' => $vault->id, 'listed' => true]);
        $contactNone = Contact::factory()->create(['vault_id' => $vault->id, 'listed' => true]);

        $contactAB->tags()->attach([$tagA->id, $tagB->id]);
        $contactA->tags()->attach($tagA->id);

        $response = $this->getJson('/api/contacts?tags[]='.$tagA->id.'&tags[]='.$tagB->id);

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($contactAB->id, $ids);
        $this->assertNotContains($contactA->id, $ids);
        $this->assertNotContains($contactNone->id, $ids);

        $responseC = $this->getJson('/api/contacts?tags[]='.$tagA->id.'&tags[]='.$tagC->id);
        $responseC->assertStatus(200);
        $this->assertEmpty($responseC->json('data'));
    }
}
