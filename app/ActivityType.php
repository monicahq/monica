<?php

namespace App;

class ActivityType extends BaseMigrationModel
{
    protected $table = 'activity_types';

    /**
     * Get the activity type group record associated with the activity types.
     */
    public function group()
    {
        return $this->belongsTo('App\ActivityTypeGroup', 'activity_type_group_id');
    }

    public function getTranslationKeyAsString()
    {
        return trans('people.activity_type_'.$this->key);
    }
}
