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
    protected $signature = 'monica:moveavatars {--force : Force the operation to run when in production.} {--dryrun : Simulate the execution but not write anything.}';

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
                    if ($contact->avatar_location == config('filesystems.default')) {
                        return;
                    }

                    try {
                        // move avatars to new location
                        $this->moveAvatarSize($contact);
                        $this->moveAvatarSize($contact, 110);
                        $this->moveAvatarSize($contact, 174);

                        if (! $this->option('dryrun')) {
                            $contact->deleteAvatars();

                            // Update location. The filename has not changed.
                            $contact->avatar_location = config('filesystems.default');
                            $contact->save();
                        }
                    } catch (FileNotFoundException $e) {
                        continue;
                    }
                }
            });
    }

    private function moveAvatarSize($contact, $size = null)
    {
        $filename = pathinfo($contact->avatar_file_name, PATHINFO_FILENAME);
        $extension = pathinfo($contact->avatar_file_name, PATHINFO_EXTENSION);

        $avatarFileName = 'avatars/'.$filename.'.'.$extension;
        if (! is_null($size)) {
            $avatarFileName = 'avatars/'.$filename.'_'.$size.'.'.$extension;
        }

        $storage = Storage::disk($contact->avatar_location);
        if (! $storage->exists($avatarFileName)) {
            if ($this->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $this->line('File not found: '.$avatarFileName);
            }

            return;
        }
        $avatarFile = $storage->get($avatarFileName);

        $newStorage = Storage::disk(config('filesystems.default'));
        if (! $this->option('dryrun')) {
            $newStorage->put($avatarFileName, $avatarFile, 'public');
        }

        if ($this->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->line('Moved file '.$avatarFileName);
        }
    }
}
