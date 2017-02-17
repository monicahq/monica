<?php

namespace App;

use App\ActivityTypeGroup;
use Illuminate\Database\Eloquent\Model;

class ActivityType extends Model
{
    protected $table = 'activity_types';

    public function getTranslationKeyAsString()
    {
        return trans('people.activity_type_'.$this->key);
    }
}
