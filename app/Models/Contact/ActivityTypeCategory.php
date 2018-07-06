<?php

namespace App\Models\Contact;

use Illuminate\Database\Eloquent\Model;

class ActivityTypeCategory extends Model
{
    protected $table = 'activity_type_categories';

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
    public function getNameAttribute()
    {
        if ($this->translation_key) {
            return trans('people.activity_type_'.$this->translation_key);
        }

        return $this->name;
    }
}
