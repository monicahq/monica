<?php

namespace App\Jobs;

use App\Mail\InvitationSent;
use Illuminate\Bus\Queueable;
use App\Models\Account\Invitation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendInvitationEmail implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $invitation;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->invitation->email)->send(new InvitationSent($this->invitation));
    }
}
