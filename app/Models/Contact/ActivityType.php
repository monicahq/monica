<?php

namespace App\Models\Contact;

use App\Models\Account\Account;
use App\Models\Contact\ActivityTypeCategory;
use Illuminate\Database\Eloquent\Model;

class ActivityType extends Model
{
    protected $table = 'activity_types';

    /**
     * Get the account record associated with the activity type.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the activity type category record associated with the activity types.
     */
    public function category()
    {
        return $this->belongsTo(ActivityTypeCategory::class, 'activity_type_category_id');
    }

    /**
     * Get the activity type's attribute.
     */
    public function getNameAttribute($value)
    {
        if ($this->translation_key) {
            return trans('people.activity_type_'.$this->translation_key);
        }

        return $value;
    }
}
