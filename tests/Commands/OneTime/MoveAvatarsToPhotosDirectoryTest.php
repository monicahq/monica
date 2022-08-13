<?php

namespace Tests\Commands\OneTime;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Photo;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MoveAvatarsToPhotosDirectoryTest extends TestCase
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
        $user = factory(User::class)->create();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        return [$user, $contact];
    }

    /** @test */
    public function it_move_avatars_to_photo_directory()
    {
        [$user, $contact] = $this->fetchUser();

        Storage::fake('public');

        Storage::disk('public')->put('avatars/avatar.jpg', 'content');
        Storage::disk('public')->put('avatars/avatar_110.jpg', 'content');
        Storage::disk('public')->put('avatars/avatar_174.jpg', 'content');

        $contact->avatar_file_name = 'avatars/avatar.jpg';
        $contact->avatar_location = 'public';
        $contact->has_avatar = true;
        $contact->save();

        Storage::disk('public')->assertExists('avatars/avatar.jpg');

        $this->artisan('monica:moveavatarstophotosdirectory')->run();

        Storage::disk('public')->assertMissing('avatars/avatar.jpg');
        Storage::disk('public')->assertMissing('avatars/avatar_110.jpg');
        Storage::disk('public')->assertMissing('avatars/avatar_174.jpg');

        $contact->refresh();
        $photo = Photo::find($contact->avatar_photo_id);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'avatar_source' => 'photo',
        ]);
        $this->assertStringContainsString('photos/', $photo->new_filename);

        Storage::disk('public')->assertExists($photo->new_filename);
    }

    /** @test */
    public function it_handles_missing_avatar()
    {
        [$user, $contact] = $this->fetchUser();

        Storage::fake('public');

        $contact->avatar_file_name = 'avatars/avatar.jpg';
        $contact->avatar_location = 'public';
        $contact->has_avatar = true;
        $contact->save();

        $this->artisan('monica:moveavatarstophotosdirectory')->run();

        Storage::disk('public')->assertMissing('avatars/avatar.jpg');

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'avatar_source' => 'default',
            'avatar_file_name' => 'avatars/avatar.jpg',
            'avatar_location' => 'public',
        ]);
    }
}
