<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\LifeEventParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class LifeEventParticipant
 *
 * Who was there. A participant is a contact or an entity of the life event's vault, which is why the
 * link is polymorphic: a walk with the dog is as legitimate a life event as a lunch with a friend.
 *
 * A participant appears once per life event. Removing one never removes the life event, since a life
 * event with nobody left in it is still a valid one.
 *
 * @property int $id
 * @property int $account_id
 * @property int $life_event_id
 * @property string $participant_type
 * @property int $participant_id
 * @property string|null $role
 * @property string|null $notes
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read LifeEvent $lifeEvent
 * @property-read Contact|Entity $participant
 */
#[Fillable([
    'account_id',
    'life_event_id',
    'participant_type',
    'participant_id',
    'role',
    'notes',
    'position',
])]
class LifeEventParticipant extends Model
{
    /** @use HasFactory<LifeEventParticipantFactory> */
    use HasFactory;

    /**
     * Get the account the participation belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the life event taken part in.
     *
     * @return BelongsTo<LifeEvent, $this>
     */
    public function lifeEvent(): BelongsTo
    {
        return $this->belongsTo(LifeEvent::class);
    }

    /**
     * Get the contact or the entity that took part.
     *
     * @return MorphTo<Model, $this>
     */
    public function participant(): MorphTo
    {
        return $this->morphTo();
    }
}
