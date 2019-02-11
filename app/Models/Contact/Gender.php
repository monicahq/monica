<?php

namespace App\Models\Contact;

use App\Models\Account\Account;
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gender extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'account_id',
    ];

    /**
     * Male type gender.
     *
     * @var string
     */
    public const MALE = 'M';

    /**
     * Female type gender.
     *
     * @var string
     */
    public const FEMALE = 'F';

    /**
     * Other type gender.
     *
     * @var string
     */
    public const OTHER = 'O';

    /**
     * Unknown type gender.
     *
     * @var string
     */
    public const UNKNOWN = 'U';

    /**
     * Get the account record associated with the gender.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact records associated with the gender.
     *
     * @return HasMany
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the name of the gender.
     *
     * @param  string  $value
     * @return string
     */
    public function getNameAttribute($value)
    {
        return $value;
    }
}
