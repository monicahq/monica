<?php

namespace App;

use App\Kid;
use App\SignificantOther;
use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    protected $dates = [
        'date_offered',
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

        return $this->name;
    }

    public function getUrl()
    {
        if (is_null($this->url)) {
            return null;
        }

        return $this->url;
    }

    public function getComment()
    {
        if (is_null($this->comment)) {
            return null;
        }

        return $this->comment;
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

    public function getWhoIsItFor()
    {
        if (is_null($this->about_object_type)) {
            return null;
        }

        if ($this->about_object_type == 'kid') {
            $kid = Kid::findOrFail($this->about_object_id);
            return $kid->getFirstName();
        }

        if ($this->about_object_type == 'significantOther') {
            $so = SignificantOther::findOrFail($this->about_object_id);
            return $so->getName();
        }
    }
}
