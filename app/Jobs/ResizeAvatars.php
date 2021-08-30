<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use Intervention\Image\Facades\Image;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ResizeAvatars implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contact;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->contact->has_avatar) {
            return;
        }

        $storage = Storage::disk($this->contact->avatar_location);
        if (! $storage->exists($this->contact->avatar_file_name)) {
            return;
        }

        try {
            $avatarFile = $storage->get($this->contact->avatar_file_name);
            $filename = pathinfo($this->contact->avatar_file_name, PATHINFO_FILENAME);
            $extension = pathinfo($this->contact->avatar_file_name, PATHINFO_EXTENSION);
        } catch (FileNotFoundException $e) {
            return;
        }

        $this->resize($avatarFile, $filename, $extension, $storage, 110);
        $this->resize($avatarFile, $filename, $extension, $storage, 174);
    }

    private function resize($avatarFile, $filename, $extension, $storage, $size)
    {
        $avatarFileName = 'avatars/'.$filename.'_'.$size.'.'.$extension;

        $avatar = Image::make($avatarFile);
        $avatar->fit($size);

        $storage->put($avatarFileName, (string) $avatar->stream(), config('filesystems.default_visibility'));
    }
}
