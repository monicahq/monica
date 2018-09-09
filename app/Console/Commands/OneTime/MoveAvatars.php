<?php

namespace App\Console\Commands\OneTime;

use App\Models\Contact\Contact;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class MoveAvatars extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:moveavatars {--force : Force the operation to run when in production.} {--dryrun : Simulate the execution but not write anything.} {--storage : new storage to move avatars to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move avatars from their current storage to current default storage';

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
                foreach ($contacts as $contact) {
                    if ($contact->avatar_location == $this->newStorage()) {
                        continue;
                    }

                    try {
                        // move avatars to new location
                        $this->moveContactAvatars($contact);

                        if (! $this->option('dryrun')) {
                            $contact->deleteAvatars();

                            // Update location. The filename has not changed.
                            $contact->avatar_location = $this->newStorage();
                            $contact->save();
                        }
                    } catch (FileNotFoundException $e) {
                        continue;
                    }
                }
            });
    }

    private function moveContactAvatars($contact)
    {
        $this->line('Contact id:'.$contact->id);
        $this->line(' > Avatar location:'.$contact->avatar_location);
        $this->line(' > File name:'.$contact->avatar_file_name);

        $avatars = [];
        array_push($avatars, $this->getFileName($contact));
        array_push($avatars, $this->getFileName($contact, 110));
        array_push($avatars, $this->getFileName($contact, 174));

        $storage = Storage::disk($contact->avatar_location);
        $newStorage = Storage::disk($this->newStorage());

        foreach ($avatars as $avatar) {
            $avatarFile = $storage->get($avatar);

            if (! $this->option('dryrun')) {
                $newStorage->put($avatar, $avatarFile, 'public');
            }

            if ($this->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $this->line('  Moved file '.$avatar);
            }
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

    private function newStorage()
    {
        return $this->option('storage') ?? config('filesystems.default');
    }
}
