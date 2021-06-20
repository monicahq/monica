<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\FeatureTestCase;
use App\Models\Account\Photo;
use App\Models\Contact\Contact;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class StorageControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /**
     * Returns an array containing a user object along with
     * a contact for that user.
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

    /** @test */
    public function it_get_photo_content()
    {
        config(['filesystems.default' => 'local']);
        Carbon::setTestNow(Carbon::create(2021, 6, 19, 7, 0, 0));

        [$user, $contact] = $this->fetchUser();

        $disk = Storage::fake('local', [
            'cache' => [
                'store' => 'file',
                'expire' => 600,
                'prefix' => 'local',
            ]
        ]);
        $adapter = $disk->getDriver()->getAdapter();
        $image = UploadedFile::fake()->image('avatar.jpg');

        $file = $disk->put('/photos', $image, 'private');

        $photo = factory(Photo::class)->create([
            'account_id' => $contact->account_id,
            'original_filename' => 'avatar.jpg',
            'filesize' => $image->getSize(),
            'mime_type' => $image->getMimeType(),
            'new_filename' => $file
        ]);

        $contact->photos()->syncWithoutDetaching([$photo->id]);

        $adapter->getCache()->updateObject($file, ['timestamp' => Carbon::now()->timestamp]);

        $response = $this->get('/store/'.$file);

        $response->assertOk();
        $response->assertHeader('Last-Modified', 'Sat, 19 Jun 2021 07:00:00 GMT');
        $response->assertHeader('Cache-Control', 'max-age=2628000, private');
    }
}
