<?php

namespace App\Jobs\Avatars;

use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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
            ->active()
            ->get();

        foreach ($contacts as $contact) {
            UpdateGravatar::dispatch($contact);
        }
    }
}
