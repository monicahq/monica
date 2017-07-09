<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Account $account
 * @property Contact $contact
 * @method static Builder forObject(Model $object, string $key = null)
 */
class Event extends Model
{
    /**
     * Get the account record associated with the event.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('App\Account');
    }

    /**
     * Get the contact record associated with the event.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo('App\Contact');
    }

    /**
     * Limits the results to a specific object
     *
     * @param Builder $query
     * @param Model $object
     * @param string|null $key
     * @return Builder
     */
    public function scopeForObject(Builder $query, Model $object, string $key = null)
    {
        if (!$key) {
            $key = strtolower(class_basename($object));
        }

        return $query->where('object_type', $key)
            ->where('object_id', $object->id);
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
