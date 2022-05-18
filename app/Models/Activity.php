<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $table = 'activities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'activity_type_id',
        'label',
    ];

    /**
     * Get the activity type associated with the activity.
     *
     * @return BelongsTo
     */
    public function activityType()
    {
        return $this->belongsTo(ActivityType::class);
    }
}
