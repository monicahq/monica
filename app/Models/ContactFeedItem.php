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
    public const ACTION_CONTACT_INFORMATION_UPDATED = 'contact_information_updated';
    public const ACTION_JOB_INFORMATION_UPDATED = 'job_information_updated';
    public const ACTION_NOTE_CREATED = 'note_created';
    public const ACTION_NOTE_UPDATED = 'note_updated';
    public const ACTION_NOTE_DESTROYED = 'note_destroyed';
    public const ACTION_GOAL_CREATED = 'goal_created';
    public const ACTION_IMPORTANT_DATE_CREATED = 'important_date_created';
    public const ACTION_IMPORTANT_DATE_UPDATED = 'important_date_updated';
    public const ACTION_IMPORTANT_DATE_DESTROYED = 'important_date_destroyed';
    public const ACTION_LABEL_ASSIGNED = 'label_assigned';
    public const ACTION_LABEL_REMOVED = 'label_removed';
    public const ACTION_CONTACT_EVENT_CREATED = 'added an event';
    public const ACTION_CONTACT_EVENT_UPDATED = 'updated an event';
    public const ACTION_CONTACT_EVENT_DESTROYED = 'deleted an event';
    public const ACTION_CONTACT_ACTIVITY_CREATED = 'added an activity';
    public const ACTION_CONTACT_ACTIVITY_UPDATED = 'updated an activity';
    public const ACTION_CONTACT_ACTIVITY_DESTROYED = 'deleted an activity';
    public const ACTION_LOAN_CREATED = 'loan_created';
    public const ACTION_LOAN_UPDATED = 'loan_updated';
    public const ACTION_ADDED_TO_GROUP = 'added_to_group';
    public const ACTION_REMOVED_FROM_GROUP = 'removed_from_group';

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
