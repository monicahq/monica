<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserInvited extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        protected User $invitedUser,
        protected User $user
    ) {}

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $invitationRoute = route('invitation.show', [
            'code' => $this->invitedUser->invitation_code,
        ]);

        return $this->markdown('emails.user.invitation')
            ->subject(trans('You are invited to join Monica'))
            ->with('userName', $this->user->name)
            ->with('url', $invitationRoute);
    }
}
