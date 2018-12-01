<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


namespace App\Notifications;

use App\Models\User\User;
use Illuminate\Bus\Queueable;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\App;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as LaravelNotification;

class StayInTouchEmail extends LaravelNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Contact
     */
    protected $contact;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array
     */
    public function via()
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  User $user
     * @return MailMessage
     */
    public function toMail(User $user) : MailMessage
    {
        App::setLocale($user->locale);

        return (new MailMessage)
            ->subject(trans('mail.stay_in_touch_subject_line', ['name' => $this->contact->name]))
            ->greeting(trans('mail.greetings', ['username' => $user->first_name]))
            ->line(trans_choice('mail.stay_in_touch_subject_description', $this->contact->stay_in_touch_frequency, [
                'name' => $this->contact->name,
                'frequency' => $this->contact->stay_in_touch_frequency,
            ]))
            ->action(trans('mail.footer_contact_info2', ['name' => $this->contact->name]), route('people.show', $this->contact));
    }

    /**
     * Use in test to check the parameter notification.
     *
     * @param Contact $contact
     * @return bool
     */
    public function assertSentFor(Contact $contact) : bool
    {
        return $contact->id == $this->contact->id;
    }
}
