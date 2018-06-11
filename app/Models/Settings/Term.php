<?php

namespace App\Models\Settings;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

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
        return $this->belongsToMany(User::class)->withPivot('user_id')->withTimestamps();
    }
}
