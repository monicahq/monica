<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\RecountsLifeEvents;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Bring Last in touch up to date for the people of a life event recorded ahead of its day, now that
 * the day has come. Nothing else writes when a date passes, so it runs nightly, and it looks at
 * yesterday as well as today so that a missed run only delays a date rather than losing it.
 */
class RecordLastInTouchOfTheDay implements ShouldQueue
{
    use Queueable;
    use RecountsLifeEvents;

    public function handle(): void
    {
        $lifeEvents = LifeEvent::query()
            ->whereDate('happened_on', '>=', CarbonImmutable::yesterday())
            ->whereDate('happened_on', '<=', CarbonImmutable::today())
            ->whereIn('life_event_type_id', LifeEventType::query()->withTrashed()->select('id')->where('updates_last_in_touch', true))
            ->select('id');

        LifeEventParticipant::query()
            ->whereIn('life_event_id', $lifeEvents)
            ->where('participant_type', Contact::class)
            ->distinct()
            ->orderBy('participant_id')
            ->pluck('participant_id')
            ->chunk(200)
            ->each(fn ($contactIds) => $this->recountLifeEventsOf($contactIds->all()));
    }
}
