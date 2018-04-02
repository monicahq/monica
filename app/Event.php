<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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
     * Limits the results to a specific object.
     *
     * @param Builder $query
     * @param Model $object
     * @param string|null $key
     * @return Builder
     */
    public function scopeForObject(Builder $query, Model $object, string $key = null)
    {
        if (! $key) {
            $key = strtolower(class_basename($object));
        }

        return $query->where('object_type', $key)
            ->where('object_id', $object->id);
    }
}
