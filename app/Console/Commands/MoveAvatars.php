<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class MoveAvatars extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:moveavatars {--force : Force the operation to run when in production.}';

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
            ->chunk(200, function ($contact) {
                if ($contact->avatar_location == config('filesystems.default')) {
                    return;
                }

                $storage = Storage::disk($contact->avatar_location);
                if (! $storage->exists($contact->avatar_file_name)) {
                    return;
                }

                try {
                    // move avatars to new location
                    $this->moveAvatarSize($contact);
                    $this->moveAvatarSize($contact, 110);
                    $this->moveAvatarSize($contact, 174);

                    $contact->deleteAvatars();
                    $contact->avatar_location = config('filesystems.default');
                } catch (FileNotFoundException $e) {
                    return;
                }
            });
    }

    private function moveAvatarSize($contact, $size = null)
    {
        $filename = pathinfo($contact->avatar_file_name, PATHINFO_FILENAME);
        $extension = pathinfo($contact->avatar_file_name, PATHINFO_EXTENSION);

        $avatar_file_name = 'avatars/'.$filename.'.'.$extension;
        if (! is_null($size)) {
            $avatar_file_name = 'avatars/'.$filename.'_'.$size.'.'.$extension;
        }

        $storage = Storage::disk($contact->avatar_location);
        $avatar_file = $storage->get('avatars/'.$avatar_file_name);

        $newStorage = Storage::disk(config('filesystems.default'));
        $newStorage->putFileAs('avatars', $avatar_file, $avatar_file_name, 'public');
    }
}
