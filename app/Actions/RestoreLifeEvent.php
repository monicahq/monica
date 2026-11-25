<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Bring a deleted life event back onto the timeline of everybody who was there, with everything it
 * held when it was deleted.
 */
class RestoreLifeEvent
{
    use RecountsLifeEvents;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly LifeEvent $lifeEvent,
    ) {}

    public function execute(): void
    {
        $this->validate();

        DB::transaction(function (): void {
            $isStillTrashed = LifeEvent::query()
                ->onlyTrashed()
                ->whereKey($this->lifeEvent->id)
                ->lockForUpdate()
                ->value('id') !== null;

            if (! $isStillTrashed) {
                throw new ModelNotFoundException('This life event is not deleted.');
            }

            $this->lifeEvent->restoreFromTheTrash();

            StructureVersion::query()
                ->whereKey($this->lifeEvent->structure_version_id)
                ->increment('record_count');

            $this->recountLifeEventsOf($this->contactIds());
        });

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LifeEventRestoration,
            vault: $this->vault,
            parameters: [
                'type' => $this->lifeEvent->lifeEventType->name,
                'name' => $this->contact->name,
            ],
            loggable: $this->lifeEvent,
        );
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->lifeEvent->account_id !== $this->user->account_id || $this->lifeEvent->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This life event is in another vault.');
        }

        if (! $this->lifeEvent->trashed()) {
            throw new ModelNotFoundException('This life event is not deleted.');
        }

        if (! in_array($this->contact->id, $this->contactIds(), true)) {
            throw new ModelNotFoundException('This contact did not take part in the life event.');
        }
    }

    /** @return array<int, int> */
    private function contactIds(): array
    {
        return LifeEventParticipant::query()
            ->where('life_event_id', $this->lifeEvent->id)
            ->where('participant_type', Contact::class)
            ->pluck('participant_id')
            ->all();
    }
}
