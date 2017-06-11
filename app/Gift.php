<?php

namespace App;

use App\Helpers\DateHelper;
use App\Events\Gift\GiftCreated;
use App\Events\Gift\GiftDeleted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    protected $dates = [
        'date_offered',
    ];

    protected $events = [
        'created' => GiftCreated::class,
        'deleted' => GiftDeleted::class,
    ];

    /**
     * Get the account record associated with the gift.
     */
    public function account()
    {
        return $this->belongsTo('App\Account');
    }

    /**
     * Get the contact record associated with the gift.
     */
    public function contact()
    {
        return $this->belongsTo('App\Contact');
    }

    public function scopeOffered(Builder $query)
    {
        return $query->where('has_been_offered', 'true');
    }

    public function scopeIsIdea(Builder $query)
    {
        return $query->where('is_an_idea', 'true');
    }

    public function getName()
    {
        if (is_null($this->name)) {
            return null;
        }

        return decrypt($this->name);
    }

    public function getUrl()
    {
        if (is_null($this->url)) {
            return null;
        }

        return decrypt($this->url);
    }

    public function getComment()
    {
        if (is_null($this->comment)) {
            return null;
        }

        return decrypt($this->comment);
    }

    public function getValue()
    {
        if (is_null($this->value_in_dollars)) {
            return null;
        }

        return $this->value_in_dollars;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }
}
