<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property User $user
 * @property Collection|Activity[] $activities
 * @property Collection|ActitivyStatistic[] $activityStatistics
 * @property Collection|Contact[] $contacts
 * @property Collection|Invitation[] $invitations
 * @property Collection|Debt[] $debts
 * @property Collection|Entry[] $entries
 * @property Collection|Gift[] $gifts
 * @property Collection|Event[] $events
 * @property Collection|Kid[] $kids
 * @property Collection|Note[] $notes
 * @property Collection|Reminder[] $reminders
 * @property Collection|SignificantOther[] $significantOthers
 * @property Collection|Task[] $tasks
 */
class Account extends Model
{
    /**
     * Get the activity records associated with the account.
     *
     * @return HasMany
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get the contact records associated with the account.
     *
     * @return HasMany
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the invitations associated with the account.
     *
     * @return HasMany
     */
    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Get the debt records associated with the account.
     *
     * @return HasMany
     */
    public function debts()
    {
        return $this->hasMany(Debt::class);
    }

    /**
     * Get the gift records associated with the account.
     *
     * @return HasMany
     */
    public function gifts()
    {
        return $this->hasMany(Gift::class);
    }

    /**
     * Get the event records associated with the account.
     *
     * @return HasMany
     */
    public function events()
    {
        return $this->hasMany(Event::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the kid records associated with the account.
     *
     * @return HasMany
     */
    public function kids()
    {
        return $this->hasMany(Kid::class);
    }

    /**
     * Get the note records associated with the account.
     *
     * @return HasMany
     */
    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Get the reminder records associated with the account.
     *
     * @return HasMany
     */
    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Get the task records associated with the account.
     *
     * @return HasMany
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the user records associated with the account.
     *
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the activity statistics record associated with the account.
     *
     * @return HasMany
     */
    public function activityStatistics()
    {
        return $this->hasMany(ActivityStatistic::class);
    }

    /**
     * Get the task records associated with the account.
     *
     * @return HasMany
     */
    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * Get the task records associated with the account.
     *
     * @return HasMany
     */
    public function significantOthers()
    {
        return $this->hasMany(SignificantOther::class);
    }
}
