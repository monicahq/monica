<?php

namespace App\Jobs\Avatars;

use App\Models\Account\Photo;
use Illuminate\Bus\Queueable;
use App\Events\MoveAvatarEvent;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use App\Exceptions\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Contact\Avatar\UpdateAvatar;

class MoveContactAvatarToPhotosDirectory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Contact
     */
    private $contact;

    /**
     * @var bool
     */
    private $dryrun;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($contact, $dryrun)
    {
        $this->contact = $contact;
        $this->dryrun = $dryrun;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // move avatars to new location
        $avatarFileName = $this->moveContactAvatars();

        if ($this->dryrun) {
            return;
        }

        // create a Photo object for this avatar
        $photo = $this->createPhotoObject($avatarFileName);

        // associate the Photo object to the contact
        $this->associatePhotoAsAvatar($photo);

        // delete original avatar
        $this->deleteOriginalAvatar($avatarFileName);

        // delete thumbnails of avatars
        $this->deleteThumbnails();
    }

    /**
     * @return string|null
     */
    private function moveContactAvatars(): ?string
    {
        Event::dispatch(new MoveAvatarEvent($this->contact));

        $storage = Storage::disk($this->contact->avatar_location);
        $avatarFileName = $this->getAvatarFileName();

        // $avatarFileName has the format `avatars/XXX.jpg`. We need to remove
        // the `avatars/` string to store the new file.
        $newAvatarFilename = str_replace('avatars/', 'photos/', $avatarFileName);

        if ($storage->exists($newAvatarFilename)) {
            return null;
        }

        if (! $this->dryrun) {
            $avatarFile = $storage->get($avatarFileName);
            $storage->put($newAvatarFilename, $avatarFile, 'public');
        }

        return $avatarFileName;
    }

    private function createPhotoObject($avatarFileName)
    {
        $newAvatarFilename = str_replace('avatars/', '', $avatarFileName);

        $photo = new Photo;
        $photo->account_id = $this->contact->account_id;
        $photo->original_filename = $newAvatarFilename;
        $photo->new_filename = 'photos/'.$newAvatarFilename;
        $photo->filesize = Storage::size('/photos/'.$newAvatarFilename);
        $photo->mime_type = 'adfad';
        $photo->save();

        return $photo;
    }

    private function associatePhotoAsAvatar($photo)
    {
        $data = [
            'account_id' => $this->contact->account_id,
            'contact_id' => $this->contact->id,
            'source' => 'photo',
            'photo_id' => $photo->id,
        ];
        app(UpdateAvatar::class)->execute($data);
    }

    private function deleteThumbnails()
    {
        try {
            $smallThumbnail = $this->getAvatarFileName(110);
            Storage::delete($smallThumbnail);
        } catch (FileNotFoundException $e) {
            // ignore
        }

        try {
            $bigThumbnail = $this->getAvatarFileName(174);
            Storage::delete($bigThumbnail);
        } catch (FileNotFoundException $e) {
            // ignore
        }
    }

    private function deleteOriginalAvatar($avatarFileName)
    {
        Storage::delete($avatarFileName);
    }

    private function getAvatarFileName($size = null)
    {
        $filename = pathinfo($this->contact->avatar_file_name, PATHINFO_FILENAME);
        $extension = pathinfo($this->contact->avatar_file_name, PATHINFO_EXTENSION);

        $avatarFileName = 'avatars/'.$filename.'.'.$extension;
        if (! is_null($size)) {
            $avatarFileName = 'avatars/'.$filename.'_'.$size.'.'.$extension;
        }

        if (! $this->fileExists($this->contact->avatar_location, $avatarFileName)) {
            throw new FileNotFoundException($avatarFileName);
        }

        return $avatarFileName;
    }

    private function fileExists($storage, $avatarFileName) : bool
    {
        $storage = Storage::disk($storage);

        return $storage->exists($avatarFileName);
    }
}
