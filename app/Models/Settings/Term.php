<?php

namespace App\Models\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Term extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the user record associated with the term.
     */
    public function users()
    {
        return $this->belongsToMany('App\User')->withPivot('user_id')->withTimestamps();
    }
}
