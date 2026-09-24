<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\LifeEventTypeIsNotAvailable;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Correct a life event from the page of one of the people who were there: what happened, when, who
 * else was there and what was written down about it.
 */
class UpdateLifeEvent
{
    use RecountsLifeEvents;

    private StructureVersion $version;

    /**
     * @param  list<Contact>  $participants  the other people who were there, in the order they were chosen
     * @param  array<int, mixed>  $values  the custom field values, keyed by field id, uploads included
     * @param  array<int, int>  $removedFieldIds  the file fields somebody removed, whose file goes with them
     */
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly LifeEvent $lifeEvent,
        private readonly LifeEventType $lifeEventType,
        private readonly CarbonImmutable $happenedOn,
        private readonly ?string $title = null,
        private readonly ?string $narrative = null,
        private readonly ?string $location = null,
        private readonly ?int $durationInMinutes = null,
        private readonly array $participants = [],
        private readonly array $values = [],
        private readonly array $removedFieldIds = [],
    ) {}

    public function execute(): LifeEvent
    {
        $before = $this->contactIds();

        $this->validate($before);

        DB::transaction(function () use ($before): void {
            if ($this->lifeEvent->life_event_type_id !== $this->lifeEventType->id) {
                $this->moveToTheNewType();
            }

            $this->lifeEvent->update([
                'happened_on' => $this->happenedOn->toDateString(),
                'title' => $this->title,
                'narrative' => $this->narrative,
                'location' => $this->location,
                'duration_in_minutes' => $this->durationInMinutes,
            ]);

            $this->syncParticipants();

            FieldValue::query()
                ->where('valuable_type', $this->lifeEvent->getMorphClass())
                ->where('valuable_id', $this->lifeEvent->id)
                ->whereIn('field_id', $this->removedFieldIds)
                ->get()
                ->each(fn (FieldValue $value) => new DestroyFieldValue(user: $this->user, fieldValue: $value)->execute());

            foreach ($this->version->assembly as $section) {
                new SetFieldValues(
                    user: $this->user,
                    record: $this->lifeEvent,
                    section: $section,
                    values: $this->values,
                )->execute();
            }

            $this->recountLifeEventsOf([...$before, ...$this->contactIds()]);
        });

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LifeEventUpdate,
            vault: $this->vault,
            parameters: [
                'type' => $this->lifeEventType->name,
                'name' => $this->contact->name,
            ],
            loggable: $this->lifeEvent,
        )->onQueue('low');

        return $this->lifeEvent->refresh();
    }

    /** @param  array<int, int>  $participantIds */
    private function validate(array $participantIds): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->lifeEvent->account_id !== $this->user->account_id || $this->lifeEvent->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This life event is in another vault.');
        }

        if (! in_array($this->contact->id, $participantIds, true)) {
            throw new ModelNotFoundException('This contact did not take part in the life event.');
        }

        foreach ($this->participants as $participant) {
            if ($participant->account_id !== $this->user->account_id || $participant->vault_id !== $this->vault->id) {
                throw new ModelNotFoundException('This participant is in another vault.');
            }
        }

        if ($this->lifeEventType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event type is in another account.');
        }

        if ($this->lifeEvent->life_event_type_id === $this->lifeEventType->id) {
            $this->version = $this->lifeEvent->structureVersion;

            return;
        }

        if (! $this->lifeEventType->is_active) {
            throw new LifeEventTypeIsNotAvailable(__('That life event type can no longer be chosen.'));
        }

        $version = $this->lifeEventType->structure?->publishedVersion;

        if (! $version instanceof StructureVersion) {
            throw new ModelNotFoundException('This life event type publishes no structure.');
        }

        $this->version = $version;
    }

    private function moveToTheNewType(): void
    {
        FieldValueRow::query()
            ->where('valuable_type', $this->lifeEvent->getMorphClass())
            ->where('valuable_id', $this->lifeEvent->id)
            ->get()
            ->each(fn (FieldValueRow $row) => new DestroyFieldValueRow(user: $this->user, row: $row)->execute());

        FieldValue::query()
            ->where('valuable_type', $this->lifeEvent->getMorphClass())
            ->where('valuable_id', $this->lifeEvent->id)
            ->get()
            ->each(fn (FieldValue $value) => new DestroyFieldValue(user: $this->user, fieldValue: $value)->execute());

        StructureVersion::query()
            ->whereKey($this->lifeEvent->structure_version_id)
            ->where('record_count', '>', 0)
            ->decrement('record_count');

        $this->version->increment('record_count');

        $this->lifeEvent->update([
            'life_event_type_id' => $this->lifeEventType->id,
            'structure_version_id' => $this->version->id,
        ]);
    }

    private function syncParticipants(): void
    {
        $people = collect([$this->contact, ...$this->participants])->unique('id')->values();

        LifeEventParticipant::query()
            ->where('life_event_id', $this->lifeEvent->id)
            ->where('participant_type', Contact::class)
            ->whereNotIn('participant_id', $people->pluck('id'))
            ->whereIn('participant_id', Contact::query()->select('id'))
            ->delete();

        foreach ($people as $position => $person) {
            LifeEventParticipant::query()->updateOrCreate([
                'life_event_id' => $this->lifeEvent->id,
                'participant_type' => $person->getMorphClass(),
                'participant_id' => $person->id,
            ], [
                'account_id' => $this->user->account_id,
                'position' => $position,
            ]);
        }

        $this->lifeEvent->update(['participant_count' => $this->lifeEvent->participants()->count()]);
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
