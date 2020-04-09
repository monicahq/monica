<?php

namespace Tests\Unit\Services\Account\Settings;

use Tests\TestCase;
use App\Models\Contact\Contact;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\Account\Photo\UploadPhoto;
use Illuminate\Validation\ValidationException;
use App\Services\Account\Settings\DestroyAllPhotos;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DestroyAllPhotosTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_all_photos()
    {
        Storage::fake();

        $contact = factory(Contact::class)->create([]);

        $photos = [];
        for ($i = 0; $i < 2; $i++) {
            $photos[] = $this->uploadPhoto($contact);
        }

        $request = [
            'account_id' => $contact->account_id,
        ];

        app(DestroyAllPhotos::class)->execute($request);

        $this->assertDatabaseMissing('photos', [
            'account_id' => $contact->account_id,
        ]);

        foreach ($photos as $photo) {
            Storage::disk('public')->assertMissing($photo->new_filename);
        }
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [];

        $this->expectException(ValidationException::class);

        app(DestroyAllPhotos::class)->execute($request);
    }

    private function uploadPhoto($contact)
    {
        $request = [
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'photo' => UploadedFile::fake()->image('imag.png'),
        ];

        $photo = app(UploadPhoto::class)->execute($request);

        Storage::disk('public')->assertExists($photo->new_filename);

        return $photo;
    }
}
