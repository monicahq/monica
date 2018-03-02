<?php

namespace App\Jobs;

use App\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Intervention\Image\Facades\Image as Image;
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
        if ($this->contact->has_avatar) {
            try {
                $avatar_file = Storage::disk($this->contact->avatar_location)->get($this->contact->avatar_file_name);
                $avatar_path = Storage::disk($this->contact->avatar_location)->url($this->contact->avatar_file_name);
                $avatar_filename_without_extension = pathinfo($avatar_path, PATHINFO_FILENAME);
                $avatar_extension = pathinfo($avatar_path, PATHINFO_EXTENSION);
            } catch (FileNotFoundException $e) {
                return;
            }

            $size = 110;
            $avatar_cropped_path = 'avatars/'.$avatar_filename_without_extension.'_'.$size.'.'.$avatar_extension;
            $avatar = Image::make($avatar_file);
            $avatar->fit($size);
            Storage::disk($this->contact->avatar_location)->put($avatar_cropped_path, $avatar->stream()->__toString());

            $size = 174;
            $avatar_cropped_path = 'avatars/'.$avatar_filename_without_extension.'_'.$size.'.'.$avatar_extension;
            $avatar = Image::make($avatar_file);
            $avatar->fit($size);
            Storage::disk($this->contact->avatar_location)->put($avatar_cropped_path, $avatar->stream()->__toString());
        }
    }
}
