<?php

namespace Tests\Unit\Services\Account\Photo;

use Tests\TestCase;
use App\Models\Account\Photo;
use App\Models\Contact\Contact;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\Account\Photo\UploadPhoto;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UploadPhotoTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_uploads_a_photo()
    {
        Storage::fake('photos');

        $contact = factory(Contact::class)->create([]);

        $file = UploadedFile::fake()->image('imag.png');

        $request = [
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'photo' => $file,
        ];

        $photo = app(UploadPhoto::class)->execute($request);

        $this->assertDatabaseHas('photos', [
            'id' => $photo->id,
            'account_id' => $contact->account_id,
            'mime_type' => 'image/png',
        ]);

        $this->assertInstanceOf(
            Photo::class,
            $photo
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 'wrong',
        ];

        $this->expectException(ValidationException::class);

        app(UploadPhoto::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_account_does_not_exist()
    {
        Storage::fake('photos');

        $request = [
            'account_id' => 0,
            'contact_id' => 0,
            'photo' => UploadedFile::fake()->image('document.pdf'),
        ];

        $this->expectException(ValidationException::class);

        app(UploadPhoto::class)->execute($request);
    }
}
