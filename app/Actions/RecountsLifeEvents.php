<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use Carbon\CarbonImmutable;

/**
 * The count of life events a contact took part in, the date of the latest, and the date of the latest
 * already past whose type updates Last in touch, worked out again from what is recorded.
 *
 * Shared by the actions that take a life event away from somebody, because a removal cannot be undone
 * by a decrement: when the life event leaving was the latest, only the records say which one is now.
 */
trait RecountsLifeEvents
{
    /** @param  array<int, int>  $contactIds */
    private function recountLifeEventsOf(array $contactIds): void
    {
        foreach (array_unique($contactIds) as $contactId) {
            $lifeEvents = LifeEvent::query()->whereIn('id', LifeEventParticipant::query()
                ->select('life_event_id')
                ->where('participant_type', Contact::class)
                ->where('participant_id', $contactId));

            $latest = (clone $lifeEvents)->max('happened_on');

            $lastInTouch = (clone $lifeEvents)
                ->whereDate('happened_on', '<=', CarbonImmutable::today())
                ->whereIn('life_event_type_id', LifeEventType::query()->withTrashed()->select('id')->where('updates_last_in_touch', true))
                ->max('happened_on');

            Contact::query()->whereKey($contactId)->update([
                'life_event_count' => $lifeEvents->count(),
                'last_life_event_at' => $latest === null ? null : CarbonImmutable::parse($latest)->startOfDay(),
                'last_interaction_at' => $lastInTouch === null ? null : CarbonImmutable::parse($lastInTouch)->startOfDay(),
            ]);
        }
    }
}
