<?php

namespace App;

use App\ActivityType;
use App\Helpers\DateHelper;
use Illuminate\Database\Eloquent\Model;
use App\Events\Activity\ActivityCreated;
use App\Events\Activity\ActivityDeleted;
use App\Events\Activity\ActivityUpdated;

class Activity extends Model
{
    protected $table = 'activities';

    protected $dates = ['date_it_happened'];

    protected $events = [
        'created' => ActivityCreated::class,
        'updated' => ActivityUpdated::class,
        'deleted' => ActivityDeleted::class,
    ];

    /**
     * Get the description for this activity.
     *
     * @return string or null
     */
    public function getDescription()
    {
        if (is_null($this->description)) {
            return null;
        }

        return decrypt($this->description);
    }

    /**
     * Get the date the activity happened.
     *
     * @return Carbon
     */
    public function getDateItHappened()
    {
        return $this->date_it_happened;
    }

    /**
     * Get the key of the title of the activity.
     *
     * @return string or null
     */
    public function getTitle()
    {
        $activityType = ActivityType::find($this->activity_type_id);

        return $activityType->key;
    }
}
