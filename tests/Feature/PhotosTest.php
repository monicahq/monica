<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\Account\Photo;
use App\Models\Contact\Contact;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PhotosTest extends FeatureTestCase
{
    use DatabaseTransactions;

    protected $jsonStructure = [
        'id',
        'object',
        'original_filename',
        'new_filename',
        'filesize',
        'mime_type',
        'link',
        'contact',
        'created_at',
        'updated_at',
    ];

    /**
     * Returns an array containing a user object along with
     * a contact for that user.
     *
     * @return array
     */
    private function fetchUser()
    {
        $user = $this->signIn();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        return [$user, $contact];
    }

    public function test_user_can_add_a_photo()
    {
        [$user, $contact] = $this->fetchUser();

        Storage::fake('public');
        $file = UploadedFile::fake()->image('avatar.jpg');

        $params = [
            'photo' => $file,
        ];

        $response = $this->post('/people/'.$contact->hashID().'/photos', $params);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonStructure,
        ]);

        // Assert the photo has been added for the correct user.
        $this->assertDatabaseHas('photos', [
            'account_id' => $user->account_id,
            'original_filename' => 'avatar.jpg',
            'new_filename' => 'photos/'.$file->hashName(),
        ]);
        $this->assertDatabaseHas('contact_photo', [
            'contact_id' => $contact->id,
            'photo_id' => $response->json('data.id'),
        ]);

        Storage::disk('public')->assertExists('photos/'.$file->hashName());
    }

    public function test_user_can_delete_a_photo()
    {
        [$user, $contact] = $this->fetchUser();

        Storage::fake('public');
        $file = UploadedFile::fake()->image('avatar.jpg');

        $params = [
            'photo' => $file,
        ];

        $response1 = $this->post('/people/'.$contact->hashID().'/photos', $params);

        $response2 = $this->delete('/people/'.$contact->hashID().'/photos/'.$response1->json('data.id'));

        $response2->assertStatus(200);

        $this->assertDatabaseMissing('photos', [
            'account_id' => $user->account_id,
            'original_filename' => 'avatar.jpg',
            'new_filename' => 'photos/'.$file->hashName(),
        ]);
        $this->assertDatabaseMissing('contact_photo', [
            'contact_id' => $contact->id,
            'photo_id' => $response1->json('data.id'),
        ]);
    }

    public function test_user_can_delete_a_photo_even_if_it_s_already_deleted()
    {
        [$user, $contact] = $this->fetchUser();

        Storage::fake('public');
        $file = UploadedFile::fake()->image('avatar.jpg');

        $params = [
            'photo' => $file,
        ];

        $response1 = $this->post('/people/'.$contact->hashID().'/photos', $params);

        Storage::delete($response1->json('data.new_filename'));

        $response2 = $this->delete('/people/'.$contact->hashID().'/photos/'.$response1->json('data.id'));

        $response2->assertStatus(200);

        $this->assertDatabaseMissing('photos', [
            'account_id' => $user->account_id,
            'original_filename' => 'avatar.jpg',
            'new_filename' => 'photos/'.$file->hashName(),
        ]);
        $this->assertDatabaseMissing('contact_photo', [
            'contact_id' => $contact->id,
            'photo_id' => $response1->json('data.id'),
        ]);
    }
}
