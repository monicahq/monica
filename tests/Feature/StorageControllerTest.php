<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\FeatureTestCase;
use App\Models\Account\Photo;
use App\Models\Contact\Contact;
use Illuminate\Http\Testing\File;
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

        [$user, $contact] = $this->fetchUser();

        $file = $this->storeImage($contact);

        $response = $this->get('/store/'.$file);

        $response->assertStatus(200);
        $response->assertHeader('Last-Modified', 'Sat, 19 Jun 2021 07:00:00 GMT');
        $response->assertHeader('Cache-Control', 'max-age=2628000, private');
        $response->assertHeader('etag', '"'.md5('/store/'.$file).'"');
    }

    /** @test */
    public function it_get_no_content_if_sent_modified_since()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $file = $this->storeImage($contact);

        $response = $this->get('/store/'.$file, [
            'If-Modified-Since' => 'Sat, 26 Jun 2021 07:00:00 GMT',
        ]);

        $response->assertNoContent(304);
        $response->assertHeaderMissing('Last-Modified');
        $response->assertHeader('Cache-Control', 'max-age=2628000, private');
        $response->assertHeader('etag', '"'.md5('/store/'.$file).'"');
    }

    /** @test */
    public function it_get_no_content_if_sent_unmodified_since()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $file = $this->storeImage($contact);

        $response = $this->get('/store/'.$file, [
            'If-Unmodified-Since' => 'Sat, 26 Jun 2021 07:00:00 GMT',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Last-Modified', 'Sat, 19 Jun 2021 07:00:00 GMT');
        $response->assertHeader('Cache-Control', 'max-age=2628000, private');
        $response->assertHeader('etag', '"'.md5('/store/'.$file).'"');
    }

    /** @test */
    public function it_fails_if_sent_unmodified_since_and_content_changed()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $file = $this->storeImage($contact);

        $response = $this->get('/store/'.$file, [
            'If-Unmodified-Since' => 'Sat, 12 Jun 2021 07:00:00 GMT',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_fails_if_file_not_found()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $response = $this->get('/store/photos/fail.png');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_fails_if_file_not_owned_by_user()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $file = $this->storeImage($contact);

        $this->signIn();

        $response = $this->get('/store/'.$file, [
            'If-Unmodified-Since' => 'Sat, 12 Jun 2021 07:00:00 GMT',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_get_no_content_if_sent_if_match()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $file = $this->storeImage($contact);

        $response = $this->get('/store/'.$file, [
            'If-Match' => '"'.md5('/store/'.$file).'"',
        ]);

        $response->assertNoContent(200);
        $response->assertHeader('Last-Modified', 'Sat, 19 Jun 2021 07:00:00 GMT');
        $response->assertHeader('Cache-Control', 'max-age=2628000, private');
        $response->assertHeader('etag', '"'.md5('/store/'.$file).'"');
    }

    /** @test */
    public function it_get_content_if_change()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $file = $this->storeImage($contact);

        $response = $this->get('/store/'.$file, [
            'If-Modified-Since' => 'Sat, 12 Jun 2021 07:00:00 GMT',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Last-Modified', 'Sat, 19 Jun 2021 07:00:00 GMT');
        $response->assertHeader('Cache-Control', 'max-age=2628000, private');
        $response->assertHeader('etag', '"'.md5('/store/'.$file).'"');
    }

    public function storeImage(Contact $contact)
    {
        $disk = Storage::fake('local', [
            'cache' => [
                'store' => 'file',
                'expire' => 600,
                'prefix' => 'local',
            ],
        ]);
        //$image = UploadedFile::fake()->image('avatar.png');
        $image = File::createWithContent('avatar.png', file_get_contents(base_path('public/img/favicon.png')));

        $file = $disk->put('/photos', $image, 'private');

        $photo = factory(Photo::class)->create([
            'account_id' => $contact->account_id,
            'original_filename' => 'avatar.png',
            'filesize' => $image->getSize(),
            'mime_type' => $image->getMimeType(),
            'new_filename' => $file,
        ]);

        $contact->photos()->syncWithoutDetaching([$photo->id]);

        $adapter = $disk->getDriver()->getAdapter();
        $adapter->getCache()->updateObject($file, [
            'timestamp' => Carbon::create(2021, 6, 19, 7, 0, 0, 'UTC')->timestamp,
        ]);

        return $file;
    }
}
