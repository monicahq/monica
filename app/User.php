<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Get the account record associated with the user.
     */
    public function account()
    {
        return $this->hasOne('App\Account');
    }

    /**
     * Indicates if the layout is fluid or not for the UI.
     * @return string
     */
    public function getFluidLayout()
    {
        if ($this->fluid_container == 'true') {
            return 'container-fluid';
        } else {
            return 'container';
        }
    }

    /**
     * @return string
     */
    public function getMetricSymbol()
    {
        if ($this->metric == 'fahrenheit') {
            return 'F';
        } else {
            return 'C';
        }
    }

    /**
     * Set the contact view preference.
     *
     * @param  string $preference
     */
    public function updateContactViewPreference($preference)
    {
        $this->contacts_sort_order = $preference;
        $this->save();
    }
}
