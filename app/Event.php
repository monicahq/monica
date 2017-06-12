<?php

namespace App;

use Auth;
use App\Reminder;
use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /**
     * Get the account record associated with the event.
     */
    public function account()
    {
        return $this->belongsTo('App\Account');
    }

    /**
     * Get the contact record associated with the event.
     */
    public function contact()
    {
        return $this->belongsTo('App\Contact');
    }

    public function getDescription()
    {
        if ($this->nature_of_operation == 'create') {
            $description = 'You added ';
        }

        if ($this->nature_of_operation == 'update') {
            $description = 'You updated ';
        }

        // You added a reminder about John Doe
        if ($this->object_type == 'reminder') {
            $reminder = Reminder::findOrFail($this->object_id);
        }
    }
}
