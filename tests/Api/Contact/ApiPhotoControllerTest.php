<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\User\User;
use App\Models\Account\Photo;
use App\Models\Contact\Contact;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiPhotoControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonDatas = [
        'id',
        'object',
        'original_filename',
        'new_filename',
        'filesize',
        'mime_type',
        'link',
        'account' => [
            'id',
        ],
        'contact' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    private function createPhoto(User $user) : Photo
    {
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $photo = factory(Photo::class)->create([
            'account_id' => $user->account_id,
        ]);

        $contact->photos()->syncWithoutDetaching([$photo->id]);

        return $photo;
    }

    public function test_it_gets_a_list_of_photos()
    {
        $user = $this->signin();

        for ($i = 0; $i < 10; $i++) {
            $this->createPhoto($user);
        }

        $response = $this->json('GET', '/api/photos');

        $response->assertStatus(200);

        $this->assertCount(
            10,
            $response->decodeResponseJson()['data']
        );

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonDatas,
            ],
        ]);
    }

    public function test_it_applies_the_limit_parameter_in_search()
    {
        $user = $this->signin();

        for ($i = 0; $i < 10; $i++) {
            $this->createPhoto($user);
        }

        $response = $this->json('GET', '/api/photos?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/photos?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '2',
            'last_page' => 5,
        ]);
    }

    public function test_it_gets_a_photo()
    {
        $user = $this->signin();

        $photo = $this->createPhoto($user);

        $response = $this->json('GET', '/api/photos/'.$photo->id);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->jsonDatas,
        ]);
    }

    public function test_it_gets_a_photo_for_a_specific_contact()
    {
        $user = $this->signin();

        $photo = $this->createPhoto($user);

        $response = $this->json('GET', '/api/contacts/'.$photo->contact()->id.'/photos');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonDatas,
            ],
        ]);

        $response->assertJsonFragment([
            'total' => 1,
            'current_page' => 1,
            'per_page' => 15,
            'last_page' => 1,
        ]);
    }

    public function test_it_store_a_photo_for_a_specific_contact()
    {
        Storage::fake();

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/contacts/'.$contact->id.'/photos', [
            'photo' => UploadedFile::fake()->image('test.jpg'),
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonDatas,
        ]);

        $this->assertDatabaseHas('photos', [
            'account_id' => $user->account_id,
            'original_filename' => 'test.jpg',
        ]);

        Storage::disk('public')->assertExists($response->json('data.new_filename'));
    }

    public function test_it_destroy_a_photo()
    {
        $user = $this->signin();

        $photo = $this->createPhoto($user);

        $response = $this->json('DELETE', '/api/photos/'.$photo->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'deleted' => true,
            'id' => $photo->id,
        ]);

        $this->assertDatabaseMissing('photos', [
            'id' => $photo->id,
            'account_id' => $user->account_id,
        ]);
    }

    public function test_it_store_and_destroy_a_photo()
    {
        Storage::fake();

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/contacts/'.$contact->id.'/photos', [
            'photo' => UploadedFile::fake()->image('test.jpg'),
        ]);

        $photo = $contact->photos->first();

        Storage::disk('public')->assertExists($photo->new_filename);

        $response = $this->json('DELETE', '/api/photos/'.$photo->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'deleted' => true,
            'id' => $photo->id,
        ]);

        Storage::disk('public')->assertMissing($photo->new_filename);
    }
}
