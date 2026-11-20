<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\RecountsLifeEvents;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Work Last in touch out again for everybody who took part in a life event of one type, after that
 * type started or stopped updating it. It is on a queue because a type used for years can reach every
 * contact of an account, which is not something a settings form should wait for.
 */
class RecountLastInTouchOfType implements ShouldQueue
{
    use Queueable;
    use RecountsLifeEvents;

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public readonly LifeEventType $lifeEventType,
    ) {}

    public function handle(): void
    {
        LifeEventParticipant::query()
            ->whereIn('life_event_id', LifeEvent::query()->where('life_event_type_id', $this->lifeEventType->id)->select('id'))
            ->where('participant_type', Contact::class)
            ->distinct()
            ->orderBy('participant_id')
            ->pluck('participant_id')
            ->chunk(200)
            ->each(fn ($contactIds) => $this->recountLifeEventsOf($contactIds->all()));
    }
}
