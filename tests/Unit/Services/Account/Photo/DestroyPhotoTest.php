<?php

namespace Tests\Unit\Services\Contact\Document;

use Tests\TestCase;
use App\Models\Account\Photo;
use App\Models\Contact\Contact;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\Account\Photo\UploadPhoto;
use App\Services\Account\Photo\DestroyPhoto;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyPhotoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_photo()
    {
        $contact = factory(Contact::class)->create([]);
        $photo = $this->uploadPhoto($contact);

        $request = [
            'account_id' => $photo->account->id,
            'photo_id' => $photo->id,
        ];

        $this->assertDatabaseHas('photos', [
            'id' => $photo->id,
        ]);

        $destroyPhotoService = new DestroyPhoto;
        $bool = $destroyPhotoService->execute($request);

        $this->assertDatabaseMissing('photos', [
            'id' => $photo->id,
        ]);

        Storage::disk('photos')->assertMissing('photo.png');
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'photo_id' => 2,
        ];

        $this->expectException(ValidationException::class);

        $destroyPhotoService = new DestroyPhoto;
        $result = $destroyPhotoService->execute($request);
    }

    public function test_it_throws_a_photo_doesnt_exist()
    {
        $photo = factory(Photo::class)->create([]);

        $request = [
            'account_id' => $photo->account->id,
            'photo_id' => 3,
        ];

        $this->expectException(ModelNotFoundException::class);

        $destroyPhotoService = new DestroyPhoto;
        $photo = $destroyPhotoService->execute($request);
    }

    private function uploadPhoto($contact)
    {
        Storage::fake('photos');

        $request = [
            'account_id' => $contact->account->id,
            'photo' => UploadedFile::fake()->image('photo.png'),
        ];

        $uploadService = new UploadPhoto;

        return $uploadService->execute($request);
    }
}
