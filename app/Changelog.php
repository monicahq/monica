<?php

namespace App;

use Parsedown;
use Illuminate\Database\Eloquent\Model;

class Changelog extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the user records associated with the tag.
     */
    public function users()
    {
        return $this->belongsToMany('App\User')->withPivot('read', 'upvote')->withTimestamps();
    }

    /**
     * Return the markdown parsed description.
     *
     * @return string
     */
    public function getDescriptionAttribute($value)
    {
        return (new Parsedown())->text($value);
    }
}
