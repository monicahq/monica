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
        if ($this->contact->has_avatar == 'true') {

            $avatar_file = Storage::disk('public')->get($this->contact->avatar_file_name);
            $avatar_path = Storage::disk('public')->url($this->contact->avatar_file_name);
            $avatar_filename_without_extension = pathinfo($avatar_path, PATHINFO_FILENAME);
            $avatar_extension = pathinfo($avatar_path, PATHINFO_EXTENSION);

            $size = 110;
            $avatar_cropped_path = 'avatars/'.$avatar_filename_without_extension.'_'.$size.'.'.$avatar_extension;
            $avatar = Image::make($avatar_file);
            $avatar->fit($size);
            Storage::disk('public')->put($avatar_cropped_path, $avatar->stream());

            $size = 174;
            $avatar_cropped_path = 'avatars/'.$avatar_filename_without_extension.'_'.$size.'.'.$avatar_extension;
            $avatar = Image::make($avatar_file);
            $avatar->fit($size);
            Storage::disk('public')->put($avatar_cropped_path, $avatar->stream());
        }
    }
}
