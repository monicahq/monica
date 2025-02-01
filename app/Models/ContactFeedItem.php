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
    public const ACTION_CONTACT_CREATED = 'contact_created';

    public const ACTION_INFORMATION_UPDATED = 'information_updated';

    public const ACTION_CONTACT_INFORMATION_CREATED = 'contact_information_created';

    public const ACTION_CONTACT_INFORMATION_UPDATED = 'contact_information_updated';

    public const ACTION_CONTACT_INFORMATION_DESTROYED = 'contact_information_destroyed';

    public const ACTION_JOB_INFORMATION_UPDATED = 'job_information_updated';

    public const ACTION_RELIGION_UPDATED = 'religion_updated';

    public const ACTION_NOTE_CREATED = 'note_created';

    public const ACTION_NOTE_UPDATED = 'note_updated';

    public const ACTION_NOTE_DESTROYED = 'note_destroyed';

    public const ACTION_PET_CREATED = 'pet_created';

    public const ACTION_PET_UPDATED = 'pet_updated';

    public const ACTION_PET_DESTROYED = 'pet_destroyed';

    public const ACTION_GOAL_CREATED = 'goal_created';

    public const ACTION_GOAL_UPDATED = 'goal_updated';

    public const ACTION_GOAL_DESTROYED = 'goal_destroyed';

    public const ACTION_IMPORTANT_DATE_CREATED = 'important_date_created';

    public const ACTION_IMPORTANT_DATE_UPDATED = 'important_date_updated';

    public const ACTION_IMPORTANT_DATE_DESTROYED = 'important_date_destroyed';

    public const ACTION_LABEL_ASSIGNED = 'label_assigned';

    public const ACTION_LABEL_REMOVED = 'label_removed';

    public const ACTION_CONTACT_ADDRESS_CREATED = 'address_created';

    public const ACTION_CONTACT_ADDRESS_UPDATED = 'address_updated';

    public const ACTION_CONTACT_ADDRESS_DESTROYED = 'address_destroyed';

    public const ACTION_LOAN_CREATED = 'loan_created';

    public const ACTION_LOAN_UPDATED = 'loan_updated';

    public const ACTION_ADDED_TO_GROUP = 'added_to_group';

    public const ACTION_REMOVED_FROM_GROUP = 'removed_from_group';

    public const ACTION_ADDED_TO_POST = 'added_to_post';

    public const ACTION_REMOVED_FROM_POST = 'removed_from_post';

    public const ACTION_ARCHIVED_CONTACT = 'archived';

    public const ACTION_UNARCHIVED_CONTACT = 'unarchived';

    public const ACTION_FAVORITED_CONTACT = 'favorited';

    public const ACTION_UNFAVORITED_CONTACT = 'unfavorited';

    public const ACTION_CHANGE_AVATAR = 'changed_avatar';

    public const ACTION_MOOD_TRACKING_EVENT_CREATED = 'mood_tracking_event_added';

    public const ACTION_MOOD_TRACKING_EVENT_UPDATED = 'mood_tracking_event_updated';

    public const ACTION_MOOD_TRACKING_EVENT_DESTROYED = 'mood_tracking_event_deleted';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the contact associated with the contact feed item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the contact information type associated with the contact feed item.
     */
    public function feedable(): MorphTo
    {
        return $this->morphTo();
    }
}
