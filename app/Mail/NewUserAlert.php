<?php

namespace App\Mail;

use App\Models\User\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewUserAlert extends Mailable
{
    use Queueable, SerializesModels;

    protected $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return self
     */
    public function build()
    {
        return $this->text('emails.registration.alert')
                    ->subject('New registration: '.$this->user->first_name.' '.$this->user->last_name)
                    ->with([
                        'user' => $this->user,
                    ]);
    }
}
