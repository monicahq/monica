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
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * @var Contact
     */
    private $contact;

    /**
     * @var bool
     */
    private $dryrun;

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    private $storage;

    /**
     * Create a new job instance.
     *
     * @param  Contact  $contact
     * @param  bool  $dryrun
     * @return void
     */
    public function __construct(Contact $contact, $dryrun)
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
        $this->storage = Storage::disk($this->contact->avatar_location);

        // move avatar to new location
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

        $newStorage = Storage::disk(config('filesystems.default'));
        $avatarFileName = $this->getAvatarFileName();

        // $avatarFileName has the format `avatars/XXX.jpg`. We need to remove
        // the `avatars/` string to store the new file.
        $newAvatarFilename = str_replace('avatars/', 'photos/', $avatarFileName);

        if ($newStorage->exists($newAvatarFilename)) {
            return null;
        }

        if (! $this->dryrun) {
            $avatarFile = $this->storage->get($avatarFileName);
            $newStorage->put($newAvatarFilename, $avatarFile, config('filesystems.default_visibility'));

            $this->contact->avatar_location = config('filesystems.default');
            $this->contact->save();
        }

        return $avatarFileName;
    }

    /**
     * @param  string|null  $avatarFileName
     * @return Photo|null
     */
    private function createPhotoObject($avatarFileName): ?Photo
    {
        if (is_null($avatarFileName)) {
            return null;
        }

        $newAvatarFilename = str_replace('avatars/', '', $avatarFileName);

        $photo = new Photo;
        $photo->account_id = $this->contact->account_id;
        $photo->original_filename = $newAvatarFilename;
        $photo->new_filename = 'photos/'.$newAvatarFilename;
        $photo->filesize = Storage::disk($this->contact->avatar_location)->size('/photos/'.$newAvatarFilename);
        $photo->mime_type = 'adfad';
        $photo->save();

        return $photo;
    }

    private function associatePhotoAsAvatar($photo)
    {
        if (is_null($photo)) {
            return;
        }

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
            $this->storage->delete($smallThumbnail);
        } catch (FileNotFoundException $e) {
            // ignore
        }

        try {
            $bigThumbnail = $this->getAvatarFileName(174);
            $this->storage->delete($bigThumbnail);
        } catch (FileNotFoundException $e) {
            // ignore
        }
    }

    private function deleteOriginalAvatar($avatarFileName)
    {
        $this->storage->delete($avatarFileName);
    }

    private function getAvatarFileName($size = null)
    {
        $filename = pathinfo($this->contact->avatar_file_name, PATHINFO_FILENAME);
        $extension = pathinfo($this->contact->avatar_file_name, PATHINFO_EXTENSION);

        $avatarFileName = 'avatars/'.$filename.'.'.$extension;
        if (! is_null($size)) {
            $avatarFileName = 'avatars/'.$filename.'_'.$size.'.'.$extension;
        }

        if (! $this->fileExists($avatarFileName)) {
            throw new FileNotFoundException($avatarFileName);
        }

        return $avatarFileName;
    }

    private function fileExists($avatarFileName): bool
    {
        return $this->storage->exists($avatarFileName);
    }
}
