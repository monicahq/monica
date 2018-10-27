<?php

namespace App\Models\Contact;

use App\Models\Account\Account;
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityType extends Model
{
    protected $table = 'activity_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'activity_type_category_id',
        'account_id',
    ];

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
     * Get the activity records associated with the activity type.
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get the activity type's attribute.
     */
    public function getNameAttribute($value)
    {
        if ($this->translation_key && ! $value) {
            return trans('people.activity_type_'.$this->translation_key);
        }

        return $value;
    }

    /**
     * Reset all associated activities with this category type.
     *
     * @return void
     */
    public function resetAssociationWithActivities()
    {
        foreach ($this->activities as $activity) {
            $activity->activity_type_id = null;
            $activity->save();
        }
    }
}
