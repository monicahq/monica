<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get the account record associated with the tag.
     */
    public function account()
    {
        return $this->belongsTo('App\Account');
    }

    /**
     * Get the contacts record associated with the tag.
     */
    public function contacts()
    {
        return $this->belongsToMany('App\Contact')->withPivot('account_id')->withTimestamps();
    }

    /**
     * Update the slug.
     */
    public function updateSlug()
    {
        $this->name_slug = str_slug($this->name);
        $this->save();
    }
}
