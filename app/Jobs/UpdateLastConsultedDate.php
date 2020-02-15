<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateLastConsultedDate implements ShouldQueue
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
     * Update the Last Consulted At field for the given contact.
     *
     * @return void
     */
    public function handle(): void
    {
        $timestamps = $this->contact->timestamps;
        $this->contact->timestamps = false;

        $this->contact->last_consulted_at = now();
        $this->contact->number_of_views = $this->contact->number_of_views + 1;

        $this->contact->save();

        $this->contact->timestamps = $timestamps;
    }
}
