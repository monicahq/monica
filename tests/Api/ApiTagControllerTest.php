<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Contact\Tag;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiTagControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonTag = [
        'id',
        'object',
        'name',
        'name_slug',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_it_get_all_tags()
    {
        $user = $this->signin();
        $tag1 = factory(Tag::class)->create([
            'account_id' => $user->account->id,
        ]);
        $tag2 = factory(Tag::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('GET', '/api/tags');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonTag,
            ],
        ]);

        $response->assertJsonFragment([
            'object' => 'tag',
            'id' => $tag1->id,
        ]);

        $response->assertJsonFragment([
            'object' => 'tag',
            'id' => $tag2->id,
        ]);
    }

    public function test_it_gets_a_specific_tag()
    {
        $user = $this->signin();
        $tag = factory(Tag::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('GET', '/api/tags/'.$tag->id);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonTag,
        ]);

        $response->assertJsonFragment([
            'object' => 'tag',
            'id' => $tag->id,
        ]);
    }

    public function test_it_triggers_error_if_tag_unknown()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/tags/0');

        $this->expectNotFound($response);
    }

    public function test_it_creates_a_tag()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/tags', [
            'name' => 'the tag',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonTag,
        ]);
        $tag_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'tag',
            'id' => $tag_id,
            'name' => 'the tag',
        ]);

        $this->assertGreaterThan(0, $tag_id);
        $this->assertDatabaseHas('tags', [
            'account_id' => $user->account->id,
            'id' => $tag_id,
            'name' => 'the tag',
        ]);
    }

    public function test_it_updates_a_tag()
    {
        $user = $this->signin();
        $tag = factory(Tag::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/tags/'.$tag->id, [
            'name' => 'the tag',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonTag,
        ]);

        $tag_id = $response->json('data.id');
        $this->assertEquals($tag->id, $tag_id);

        $response->assertJsonFragment([
            'object' => 'tag',
            'id' => $tag_id,
            'name' => 'the tag',
        ]);

        $this->assertGreaterThan(0, $tag_id);
        $this->assertDatabaseHas('tags', [
            'account_id' => $user->account->id,
            'id' => $tag_id,
            'name' => 'the tag',
        ]);
    }

    public function test_it_deletes_a_tag()
    {
        $user = $this->signin();
        $tag = factory(Tag::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('DELETE', '/api/tags/'.$tag->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('contact_tag', [
            'account_id' => $user->account->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseMissing('tags', [
            'account_id' => $user->account->id,
            'id' => $tag->id,
        ]);
    }

    public function test_it_deletes_a_tag_associated()
    {
        $user = $this->signin();
        $tag = factory(Tag::class)->create([
            'account_id' => $user->account->id,
        ]);
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', "/api/contacts/{$contact1->id}/setTags", ['tags' => [$tag->name]]);
        $response = $this->json('POST', "/api/contacts/{$contact2->id}/setTags", ['tags' => [$tag->name]]);

        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseHas('contact_tag', [
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
            'tag_id' => $tag->id,
        ]);

        $response = $this->json('DELETE', '/api/tags/'.$tag->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('contact_tag', [
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseMissing('contact_tag', [
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseMissing('tags', [
            'account_id' => $user->account->id,
            'id' => $tag->id,
        ]);
    }
}
