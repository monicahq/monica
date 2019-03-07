<?php

namespace App\Console\Commands\OneTime;

use App\Models\Account\Photo;
use App\Models\Contact\Contact;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\ConfirmableTrait;
use App\Services\Contact\Avatar\UpdateAvatar;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

/**
 * This command moves current avatars to the new Photos directory and converts
 * each avatar to a Photo object.
 */
class MoveAvatarsToPhotosDirectory extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:moveavatarstophotosdirectory
                            {--force : Force the operation to run when in production.}
                            {--dryrun : Simulate the execution but not write anything.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move avatars to the Photos directory, and create a photo object for each one of them';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        Contact::where('has_avatar', true)
            ->chunk(200, function ($contacts) {
                $this->handleContacts($contacts);
            });
    }

    private function handleContacts($contacts)
    {
        foreach ($contacts as $contact) {
            try {
                $this->handleOneContact($contact);
            } catch (FileNotFoundException $e) {
                continue;
            }
        }
    }

    private function handleOneContact($contact)
    {
        // move avatars to new location
        $this->moveContactAvatars($contact);

        // create a Photo object for this avatar
        $photo = $this->createPhotoObject($contact);

        // associate the Photo object to the contact
        $this->associatePhotoAsAvatar($contact, $photo);

        // delete original avatar
        $this->deleteOriginalAvatar($contact);

        // delete thumbnails of avatars
        $this->deleteThumbnails($contact);
    }

    private function moveContactAvatars($contact)
    {
        $this->line('Contact id:'.$contact->id.' | Avatar location:'.$contact->avatar_location.' | File name:'.$contact->avatar_file_name);

        $storage = Storage::disk($contact->avatar_location);
        $avatarFileName = $this->getAvatarFileName($contact);

        // $avatarFileName has the format `avatars/XXX.jpg`. We need to remove
        // the `avatars/` string to store the new file.
        $newAvatarFilename = str_replace('avatars/', 'photos/', $avatarFileName);

        if ($storage->exists('photos/'.$avatarFileName)) {
            return;
        }

        if (! $this->option('dryrun')) {
            $avatarFile = $storage->get($avatarFileName);
            $storage->put($newAvatarFilename, $avatarFile, 'public');
        }
    }

    private function createPhotoObject($contact)
    {
        $newAvatarFilename = str_replace('avatars/', '', $this->getAvatarFileName($contact));

        $photo = new Photo;
        $photo->account_id = $contact->account_id;
        $photo->original_filename = $newAvatarFilename;
        $photo->new_filename = 'photos/'.$newAvatarFilename;
        $photo->filesize = Storage::size('/photos/'.$newAvatarFilename);
        $photo->mime_type = 'adfad';
        $photo->save();

        return $photo;
    }

    private function associatePhotoAsAvatar($contact, $photo)
    {
        $data = [
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'source' => 'photo',
            'photo_id' => $photo->id,
        ];
        app(UpdateAvatar::class)->execute($data);
    }

    private function deleteThumbnails($contact)
    {
        $smallThumbnail = $this->getAvatarFileName($contact, 110);
        if (! $this->fileExists($contact->avatar_location, $smallThumbnail)) {
            return;
        }

        $bigThumbnail = $this->getAvatarFileName($contact, 174);
        if (! $this->fileExists($contact->avatar_location, $bigThumbnail)) {
            return;
        }

        Storage::delete($smallThumbnail);
        Storage::delete($bigThumbnail);
    }

    private function deleteOriginalAvatar($contact)
    {
        $avatar = $this->getAvatarFileName($contact);
        Storage::delete($avatar);
    }

    private function getAvatarFileName($contact, $size = null)
    {
        $filename = pathinfo($contact->avatar_file_name, PATHINFO_FILENAME);
        $extension = pathinfo($contact->avatar_file_name, PATHINFO_EXTENSION);

        $avatarFileName = 'avatars/'.$filename.'.'.$extension;
        if (! is_null($size)) {
            $avatarFileName = 'avatars/'.$filename.'_'.$size.'.'.$extension;
        }

        if ($this->fileExists($contact->avatar_location, $avatarFileName)) {
            return $avatarFileName;
        }
    }

    private function fileExists($storage, $avatarFileName) : bool
    {
        $storage = Storage::disk($storage);

        if (! $storage->exists($avatarFileName)) {
            if ($this->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $this->line('  ! File not found: '.$avatarFileName);
            }
            throw new FileNotFoundException();
        }

        return true;
    }
}
