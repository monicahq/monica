<?php

namespace App\Jobs\Avatars;

use App\Models\Contact\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class UpdateAllGravatars implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $contacts = Contact::where('avatar_source', 'gravatar')
            ->orWhere('avatar_gravatar_url', '<>', '')
            ->get();

        foreach ($contacts as $contact) {
            UpdateGravatar::dispatch($contact);
        }
    }
}
