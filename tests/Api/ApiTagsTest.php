<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Contact\Tag;
use App\Models\Account\Account;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiTagsTest extends ApiTestCase
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

    public function test_tags_get_all_tags()
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
            'data' => ['*' => $this->jsonTag],
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

    public function test_tags_get_one_tag()
    {
        $user = $this->signin();
        $tag1 = factory(Tag::class)->create([
            'account_id' => $user->account->id,
        ]);
        $tag2 = factory(Tag::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('GET', '/api/tags/'.$tag1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonTag,
        ]);
        $response->assertJsonFragment([
            'object' => 'tag',
            'id' => $tag1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'tag',
            'id' => $tag2->id,
        ]);
    }

    public function test_tags_get_one_tag_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/tags/0');

        $this->expectNotFound($response);
    }

    public function test_tags_create_tag()
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

    public function test_tags_create_tag_error()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/tags', []);

        $this->expectDataError($response, [
            'The name field is required.',
        ]);
    }

    public function test_tags_update_tag()
    {
        $user = $this->signin();
        $tag = factory(Tag::class)->create([
            'account_id' => $user->account->id,
            'name' => 'old name',
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

    public function test_tags_delete_tag()
    {
        $user = $this->signin();
        $tag = factory(Tag::class)->create([
            'account_id' => $user->account->id,
        ]);
        $this->assertDatabaseHas('tags', [
            'account_id' => $user->account->id,
            'id' => $tag->id,
        ]);

        $response = $this->json('DELETE', '/api/tags/'.$tag->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tags', [
            'account_id' => $user->account->id,
            'id' => $tag->id,
        ]);
    }
}
