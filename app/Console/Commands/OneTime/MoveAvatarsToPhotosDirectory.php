<?php

namespace App\Console\Commands\OneTime;

use App\Models\Contact\Contact;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use App\Models\Account\Photo;

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

        if (! $this->option('dryrun')) {
            $contact->deleteAvatars();
        }

        // delete thumbnails of avatars
        $this->deleteThumbnails($contact);

        // create a Photo object for this avatar
        $photo = $this->createPhotoObject($contact);

        // associate the Photo object to the contact
        $this->associatePhoto($contact, $photo);
    }

    private function moveContactAvatars($contact)
    {
        $this->line('Contact id:'.$contact->id.' | Avatar location:'.$contact->avatar_location.' | File name:'.$contact->avatar_file_name);

        $avatarFileName = $this->getFileName($contact);
        $storage = Storage::disk($contact->avatar_location);

        if ($storage->exists('photos/' . $avatarFileName)) {
            return;
        }

        if (! $this->option('dryrun')) {
            $avatarFile = $storage->get($avatarFileName);
            $storage->put('photos/' . $avatarFileName, $avatarFile, 'public');
        }
    }

    private function getFileName($contact, $size = null)
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

    private function deleteThumbnails($contact)
    {
        $smallThumbnail = $this->getFileName($contact, 110);
        if (! $this->fileExists($contact->avatar_location, $avatarFileName)) {
            return;
        }

        $bigThumbnail = $this->getFileName($contact, 174);
        if (! $this->fileExists($contact->avatar_location, $avatarFileName)) {
            return;
        }

        Storage::delete($smallThumbnail);
        Storage::delete($bigThumbnail);
    }

    private function createPhotoObject($contact)
    {
        $filename = pathinfo($contact->avatar_file_name, PATHINFO_FILENAME) . pathinfo($contact->avatar_file_name, PATHINFO_EXTENSION);

        $photo = new Photo;
        $photo->original_filename = $filename;
        $photo->new_filename = $filename;
        $photo->filesize = filesize($filename);
        $photo->mime_type = 'adfad';
        $photo->save();

        return $photo;
    }

    private function associatePhoto($contact, $photo)
    {
        $data = [
            'account_id' => auth()->user()->account->id,
            'contact_id' => $contact->id,
            'source' => $request->get('avatar'),
            'photo_id' => $photo->id,
        ];
        (new UpdateAvatar)->execute($data);
    }
}
