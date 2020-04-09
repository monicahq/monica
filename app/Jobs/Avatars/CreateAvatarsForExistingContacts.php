<?php

namespace App\Jobs\Avatars;

use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * This creates all the avatars (default, adorable and gravatars) for existing
 * contacts.
 */
class CreateAvatarsForExistingContacts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * Determine the time at which this job should timeout.
     *
     * @return \Carbon\Carbon
     */
    public function retryUntil()
    {
        $totalContact = Contact::whereNull('avatar_adorable_url')
            ->orWhere('avatar_default_url', 'not like', 'avatars/%')
            ->count();

        return now()->addSeconds($totalContact / 500);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $delay = $this->retryUntil();

        Contact::without(['account', 'avatarPhoto', 'gender'])
            ->whereNull('avatar_adorable_url')
            ->orWhere('avatar_default_url', 'not like', 'avatars/%')
            ->chunk(1000, function ($contacts) use ($delay) {
                foreach ($contacts as $contact) {
                    GetAvatarsFromInternet::dispatch($contact)
                        ->delay($delay);
                    GenerateDefaultAvatar::dispatch($contact)
                        ->delay($delay);
                }
                $delay = $delay->addMinutes(1);
            });
    }
}
