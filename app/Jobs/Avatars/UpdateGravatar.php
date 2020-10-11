<?php

namespace App\Jobs\Avatars;

use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Contact\Avatar\GetGravatar;

class UpdateGravatar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Contact to treat.
     *
     * @var Contact
     */
    public $contact;

    /**
     * Create a new job instance.
     *
     * @param  Contact $contact
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
        // generate the default avatar
        app(GetGravatar::class)->execute([
            'contact_id' => $this->contact->id,
        ]);
    }
}
