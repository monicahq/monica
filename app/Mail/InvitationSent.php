<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\Account\Invitation;
use Illuminate\Queue\SerializesModels;

class InvitationSent extends Mailable
{
    use Queueable, SerializesModels;

    protected $invitation;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Build the message.
     *
     * @return self
     */
    public function build()
    {
        return $this->view('emails.invitation.index')
                    ->subject('You are invited to join Monica')
                    ->with([
                        'invitation' => $this->invitation,
                    ]);
    }
}
