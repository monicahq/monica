<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    /**
     * Get the activity records associated with the account.
     */
    public function activities()
    {
        return $this->hasMany('App\Activity');
    }

    /**
     * Get the contact records associated with the account.
     */
    public function contacts()
    {
        return $this->hasMany('App\Contact')->orderBy('updated_at', 'desc');
    }

    /**
     * Get the debt records associated with the account.
     */
    public function debt()
    {
        return $this->hasMany('App\Debt');
    }

    /**
     * Get the gift records associated with the account.
     */
    public function gifts()
    {
        return $this->hasMany('App\Gift');
    }

    /**
     * Get the event records associated with the account.
     */
    public function events()
    {
        return $this->hasMany('App\Event')->orderBy('created_at', 'desc');
    }

    /**
     * Get the kid records associated with the account.
     */
    public function kids()
    {
        return $this->hasMany('App\Kid');
    }

    /**
     * Get the note records associated with the account.
     */
    public function notes()
    {
        return $this->hasMany('App\Note');
    }

    /**
     * Get the reminder records associated with the account.
     */
    public function reminders()
    {
        return $this->hasMany('App\Reminder');
    }

    /**
     * Get the task records associated with the account.
     */
    public function tasks()
    {
        return $this->hasMany('App\Task');
    }

    /**
     * Get the user record associated with the account.
     */
    public function user()
    {
        return $this->hasOne('App\User');
    }
}
