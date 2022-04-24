<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\FeatureTestCase;
use App\Models\Account\Photo;
use App\Helpers\StorageHelper;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class StorageControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

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
        $response->assertHeader('etag', '"'.sha1('/store/'.$file).'"');
    }

    /** @test */
    public function it_get_avatar_content()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $file = $this->storeAvatar($contact);

        $response = $this->get('/store/'.$file);

        $response->assertStatus(200);
        $response->assertHeader('Last-Modified', 'Sat, 19 Jun 2021 07:00:00 GMT');
        $response->assertHeader('Cache-Control', 'max-age=2628000, private');
        $response->assertHeader('etag', '"'.sha1('/store/'.$file).'"');
    }

    /** @test */
    public function it_get_document_content()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $file = $this->storeDocument($contact);

        $response = $this->get('/store/'.$file);

        $response->assertStatus(200);
        $response->assertHeader('Last-Modified', 'Sat, 19 Jun 2021 07:00:00 GMT');
        $response->assertHeader('Cache-Control', 'max-age=2628000, private');
        $response->assertHeader('etag', '"'.sha1('/store/'.$file).'"');
    }

    /** @test */
    public function it_returns_404_if_avatar_not_exist()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $response = $this->get('/store/avatars/test');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_404_if_folder_unknown()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $response = $this->get('/store/xxx/test');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_200_if_modified_after_IfModifiedSince()
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
        $response->assertHeader('etag', '"'.sha1('/store/'.$file).'"');
    }

    /** @test */
    public function it_returns_304_if_not_modified_since_IfModifiedSince()
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
        $response->assertHeader('etag', '"'.sha1('/store/'.$file).'"');
    }

    /** @test */
    public function it_returns_200_if_not_modified_after_IfUnmodifiedSince()
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
        $response->assertHeader('etag', '"'.sha1('/store/'.$file).'"');
    }

    /** @test */
    public function it_returns_412_if_modified_after_IfUnmodifiedSince()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $file = $this->storeImage($contact);

        $response = $this->get('/store/'.$file, [
            'If-Unmodified-Since' => 'Sat, 12 Jun 2021 07:00:00 GMT',
        ]);

        $response->assertStatus(412);
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
    public function it_fails_if_file_not_exist()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $photo = factory(Photo::class)->create([
            'account_id' => $contact->account_id,
            'original_filename' => 'avatar.png',
            'filesize' => 0,
            'mime_type' => '',
            'new_filename' => 'avatar.png',
        ]);

        $contact->photos()->syncWithoutDetaching([$photo->id]);

        $response = $this->get('/store/photos/avatar.png');

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
    public function it_returns_200_if_matching_IfMatch()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $file = $this->storeImage($contact);

        $response = $this->get('/store/'.$file, [
            'If-Match' => '"'.sha1('/store/'.$file).'"',
        ]);

        $response->assertNoContent(200);
        $response->assertHeader('Last-Modified', 'Sat, 19 Jun 2021 07:00:00 GMT');
        $response->assertHeader('Cache-Control', 'max-age=2628000, private');
        $response->assertHeader('etag', '"'.sha1('/store/'.$file).'"');
    }

    /** @test */
    public function it_returns_200_with_none_matching_IfNoneMatch()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $file = $this->storeImage($contact);

        $response = $this->get('/store/'.$file, [
            'If-None-Match' => '"test"',
        ]);

        $response->assertNoContent(200);
        $response->assertHeader('Last-Modified', 'Sat, 19 Jun 2021 07:00:00 GMT');
        $response->assertHeader('Cache-Control', 'max-age=2628000, private');
        $response->assertHeader('etag', '"'.sha1('/store/'.$file).'"');
    }

    /** @test */
    public function it_returns_304_if_matching_IfNoneMatch()
    {
        config(['filesystems.default' => 'local']);

        [$user, $contact] = $this->fetchUser();

        $file = $this->storeImage($contact);

        $response = $this->get('/store/'.$file, [
            'If-None-Match' => '"'.sha1('/store/'.$file).'"',
        ]);

        $response->assertNoContent(304);
        $response->assertHeaderMissing('Last-Modified');
        $response->assertHeader('Cache-Control', 'max-age=2628000, private');
        $response->assertHeader('etag', '"'.sha1('/store/'.$file).'"');
    }

    public function storeImage(Contact $contact)
    {
        Storage::fake('local');
        $image = File::createWithContent('avatar.png', file_get_contents(base_path('public/img/favicon.png')));

        $file = Storage::putFile('/photos', $image, [
            'disk' => 'local',
        ]);

        $photo = factory(Photo::class)->create([
            'account_id' => $contact->account_id,
            'original_filename' => 'avatar.png',
            'filesize' => $image->getSize(),
            'mime_type' => $image->getMimeType(),
            'new_filename' => $file,
        ]);

        $contact->photos()->syncWithoutDetaching([$photo->id]);

        touch(StorageHelper::disk('local')->path($file), Carbon::create(2021, 6, 19, 7, 0, 0, 'UTC')->timestamp);

        return $file;
    }

    public function storeDocument(Contact $contact)
    {
        Storage::fake('local');
        $image = File::createWithContent('file.png', file_get_contents(base_path('public/img/favicon.png')));

        $file = Storage::putFile('/documents', $image, [
            'disk' => 'local',
        ]);

        factory(Document::class)->create([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'original_filename' => 'file.png',
            'new_filename' => $file,
        ]);

        touch(StorageHelper::disk('local')->path($file), Carbon::create(2021, 6, 19, 7, 0, 0, 'UTC')->timestamp);

        return $file;
    }

    public function storeAvatar(Contact $contact)
    {
        $disk = Storage::fake('local');
        $image = File::createWithContent('avatar.png', file_get_contents(base_path('public/img/favicon.png')));

        $file = Storage::putFile('/avatars', $image, [
            'disk' => 'local',
        ]);

        $contact->avatar_source = 'default';
        $contact->avatar_default_url = $file.'?123';
        $contact->save();

        touch(StorageHelper::disk('local')->path($file), Carbon::create(2021, 6, 19, 7, 0, 0, 'UTC')->timestamp);

        return $file;
    }
}
