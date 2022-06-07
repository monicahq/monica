<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContactFeedItem extends Model
{
    use HasFactory;

    protected $table = 'contact_feed_items';

    /**
     * Possible actions.
     */
    const ACTION_CONTACT_INFORMATION_UPDATED = 'contact_information_updated';
    const ACTION_JOB_INFORMATION_UPDATED = 'job_information_updated';
    const ACTION_NOTE_CREATED = 'note_created';
    const ACTION_NOTE_UPDATED = 'note_updated';
    const ACTION_NOTE_DESTROYED = 'note_destroyed';
    const ACTION_GOAL_CREATED = 'goal_created';
    const ACTION_IMPORTANT_DATE_CREATED = 'important_date_created';
    const ACTION_IMPORTANT_DATE_UPDATED = 'important_date_updated';
    const ACTION_IMPORTANT_DATE_DESTROYED = 'important_date_destroyed';
    const ACTION_LABEL_ASSIGNED = 'label_assigned';
    const ACTION_LABEL_REMOVED = 'label_removed';
    const ACTION_CONTACT_EVENT_CREATED = 'added an event';
    const ACTION_CONTACT_EVENT_UPDATED = 'updated an event';
    const ACTION_CONTACT_EVENT_DESTROYED = 'deleted an event';
    const ACTION_CONTACT_ACTIVITY_CREATED = 'added an activity';
    const ACTION_CONTACT_ACTIVITY_UPDATED = 'updated an activity';
    const ACTION_CONTACT_ACTIVITY_DESTROYED = 'deleted an activity';
    const ACTION_LOAN_CREATED = 'loan_created';
    const ACTION_LOAN_UPDATED = 'loan_updated';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'author_id',
        'contact_id',
        'action',
        'description',
        'feedable_id',
        'feedable_type',
    ];

    /**
     * Get the user associated with the contact feed item.
     *
     * @return BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the contact associated with the contact feed item.
     *
     * @return BelongsTo
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the contact information type associated with the contact feed item.
     *
     * @return MorphTo
     */
    public function feedable(): MorphTo
    {
        return $this->morphTo();
    }
}
