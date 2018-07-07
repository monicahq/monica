<?php

namespace App\Models\Contact;

use App\Models\Account\Account;
use App\Models\Contact\ActivityType;
use Illuminate\Database\Eloquent\Model;

class ActivityTypeCategory extends Model
{
    protected $table = 'activity_type_categories';

    protected $appends = ['name'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'account_id',
    ];

    /**
     * Get the account record associated with the activity type group.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the activity type records associated with the category.
     *
     * @return HasMany
     */
    public function activityTypes()
    {
        return $this->hasMany(ActivityType::class);
    }

    /**
     * Get the activity type category's attribute.
     *
     * @return string
     */
    public function getNameAttribute($value)
    {
        if ($this->translation_key) {
            return trans('people.activity_type_'.$this->translation_key);
        }

        return $value;
    }
}
