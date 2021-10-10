<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\Contact\Contact;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiAvatarControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonDatas = [
        'id',
        'object',
        'account' => [
            'id',
        ],
        'information' => [
            'avatar' => [
                'url',
                'source',
                'default_avatar_color',
            ],
        ],
        'created_at',
        'updated_at',
    ];

    /** @test */
    public function it_updates_the_photo_avatar()
    {
        Storage::fake();

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/photos', [
            'contact_id' => $contact->id,
            'photo' => UploadedFile::fake()->image('test.jpg'),
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('photos', [
            'account_id' => $user->account_id,
            'original_filename' => 'test.jpg',
        ]);

        $photo = $contact->photos->first();

        Storage::disk('public')->assertExists($photo->new_filename);

        $response = $this->json('PUT', '/api/contacts/'.$contact->id.'/avatar', [
            'photo' => UploadedFile::fake()->image('test.jpg'),
            'source' => 'photo',
            'photo_id' => $photo->id,
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->jsonDatas,
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'avatar_source' => 'photo',
            'avatar_photo_id' => $photo->id,
        ]);
    }

    /** @test */
    public function it_updates_the_gravatar_avatar()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'avatar_gravatar_url' => 'a gravatar url',
        ]);

        $response = $this->json('PUT', '/api/contacts/'.$contact->id.'/avatar', [
            'source' => 'gravatar',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->jsonDatas,
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'avatar_source' => 'gravatar',
        ]);
    }

    /** @test */
    public function it_updates_the_adorable_avatar()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/contacts/'.$contact->id.'/avatar', [
            'source' => 'adorable',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->jsonDatas,
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'avatar_source' => 'adorable',
        ]);
    }

    /** @test */
    public function it_updates_the_default_avatar()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/contacts/'.$contact->id.'/avatar', [
            'source' => 'default',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->jsonDatas,
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'avatar_source' => 'default',
        ]);
    }

    /** @test */
    public function avatar_update_gets_an_error_if_fields_are_missing()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/contacts/'.$contact->id.'/avatar', [
            'source' => 'blabla',
        ]);

        $this->expectDataError($response, [
            'The selected source is invalid.',
        ]);
    }

    /** @test */
    public function avatar_update_gets_an_error_if_contact_is_not_linked_to_user()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create();

        $response = $this->json('PUT', '/api/contacts/'.$contact->id.'/avatar', [
            'source' => 'default',
        ]);

        $this->expectNotFound($response);
    }
}
