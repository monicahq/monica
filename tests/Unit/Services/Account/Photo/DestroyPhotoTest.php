<?php

namespace Tests\Unit\Services\Account\Photo;

use Tests\TestCase;
use App\Models\Account\Photo;
use App\Models\Account\Account;
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

    /** @test */
    public function it_destroys_a_photo()
    {
        $contact = factory(Contact::class)->create([]);
        $photo = $this->uploadPhoto($contact);

        $request = [
            'account_id' => $photo->account_id,
            'photo_id' => $photo->id,
        ];

        $this->assertDatabaseHas('photos', [
            'id' => $photo->id,
        ]);

        app(DestroyPhoto::class)->execute($request);

        $this->assertDatabaseMissing('photos', [
            'id' => $photo->id,
        ]);

        Storage::disk('photos')->assertMissing('photo.png');
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'photo_id' => 2,
        ];

        $this->expectException(ValidationException::class);

        app(DestroyPhoto::class)->execute($request);
    }

    /** @test */
    public function it_throws_a_photo_doesnt_exist()
    {
        $account = factory(Account::class)->create([]);
        $photo = factory(Photo::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'photo_id' => $photo->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        app(DestroyPhoto::class)->execute($request);
    }

    private function uploadPhoto($contact)
    {
        Storage::fake('photos');

        $request = [
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'photo' => UploadedFile::fake()->image('photo.png'),
        ];

        return app(UploadPhoto::class)->execute($request);
    }
}
