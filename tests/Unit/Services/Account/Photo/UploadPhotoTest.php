<?php

namespace Tests\Unit\Services\Account\Photo;

use Tests\TestCase;
use App\Models\Account\Photo;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\Account\Photo\UploadPhoto;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UploadPhotoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_uploads_a_photo()
    {
        Storage::fake('photos');

        $contact = factory(Contact::class)->create([]);

        $file = UploadedFile::fake()->image('imag.png');

        $request = [
            'account_id' => $contact->account->id,
            'photo' => $file,
        ];

        $uploadService = new UploadPhoto;
        $photo = $uploadService->execute($request);

        $this->assertDatabaseHas('photos', [
            'id' => $photo->id,
            'account_id' => $contact->account->id,
            'mime_type' => 'image/png',
        ]);

        $this->assertInstanceOf(
            Photo::class,
            $photo
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
        ];

        $this->expectException(ValidationException::class);

        $uploadService = new UploadPhoto;
        $photo = $uploadService->execute($request);
    }

    public function test_it_throws_an_exception_if_account_does_not_exist()
    {
        Storage::fake('photos');

        $request = [
            'account_id' => 12345,
            'photo' => UploadedFile::fake()->image('document.pdf'),
        ];

        $this->expectException(ValidationException::class);

        $uploadService = (new UploadPhoto)->execute($request);
    }
}
