<?php

namespace App\Models\User;

use Parsedown;
use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Model;

class Changelog extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<string>
     */
    protected $dates = [
        'created_at',
    ];

    /**
     * Get the user records associated with the tag.
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('read', 'upvote')->withTimestamps();
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

    /**
     * Return the created_at date in a friendly format.
     *
     * @return string
     */
    public function getCreatedAtAttribute($value)
    {
        return DateHelper::getShortDate($value);
    }
}
