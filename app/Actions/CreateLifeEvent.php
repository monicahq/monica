<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DatePrecisionEnum;
use App\Enums\LifeEventSourceEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\LifeEventTypeIsNotAvailable;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Group;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Record something that happened, from the page of one of the people who were there, from the vault
 * dashboard, from a group's page, or from outside through the API or MCP, and put it on the timeline of
 * every one of them. Recorded from a group's page, it also belongs to that group's history.
 */
class CreateLifeEvent
{
    private LifeEvent $lifeEvent;

    private StructureVersion $version;

    /**
     * @param  list<Contact>  $participants  the other people who were there, in the order they were chosen
     * @param  array<int, mixed>  $values  the custom field values, keyed by field id, uploads included
     */
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly LifeEventType $lifeEventType,
        private readonly CarbonImmutable $happenedOn,
        private readonly ?string $title = null,
        private readonly ?string $narrative = null,
        private readonly ?string $location = null,
        private readonly ?int $durationInMinutes = null,
        private readonly array $participants = [],
        private readonly array $values = [],
        private readonly LifeEventSourceEnum $createdFrom = LifeEventSourceEnum::Contact,
        private readonly ?Group $group = null,
    ) {}

    public function execute(): LifeEvent
    {
        $this->validate();

        $people = collect([$this->contact, ...$this->participants])->unique('id')->values();

        DB::transaction(function () use ($people): void {
            $this->create($people->count());

            foreach ($people as $position => $person) {
                LifeEventParticipant::query()->create([
                    'account_id' => $this->user->account_id,
                    'life_event_id' => $this->lifeEvent->id,
                    'participant_type' => $person->getMorphClass(),
                    'participant_id' => $person->id,
                    'position' => $position,
                ]);

                $this->countOn($person);
            }

            $this->version->increment('record_count');

            foreach ($this->version->assembly as $section) {
                new SetFieldValues(
                    user: $this->user,
                    record: $this->lifeEvent,
                    section: $section,
                    values: $this->values,
                )->execute();
            }
        });

        $this->log();

        return $this->lifeEvent;
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->contact->account_id !== $this->user->account_id || $this->contact->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This contact is in another vault.');
        }

        foreach ($this->participants as $participant) {
            if ($participant->account_id !== $this->user->account_id || $participant->vault_id !== $this->vault->id) {
                throw new ModelNotFoundException('This participant is in another vault.');
            }
        }

        if ($this->group instanceof Group && ($this->group->account_id !== $this->user->account_id || $this->group->vault_id !== $this->vault->id)) {
            throw new ModelNotFoundException('This group is in another vault.');
        }

        if ($this->lifeEventType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event type is in another account.');
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

    private function create(int $participantCount): void
    {
        $this->lifeEvent = LifeEvent::query()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'life_event_type_id' => $this->lifeEventType->id,
            'structure_version_id' => $this->version->id,
            'group_id' => $this->group?->id,
            'happened_on' => $this->happenedOn->toDateString(),
            'date_precision' => DatePrecisionEnum::Day,
            'title' => $this->title,
            'narrative' => $this->narrative,
            'location' => $this->location,
            'duration_in_minutes' => $this->durationInMinutes,
            'created_from' => $this->createdFrom,
            'created_by_user_id' => $this->user->id,
            'participant_count' => $participantCount,
        ]);
    }

    private function countOn(Contact $person): void
    {
        $day = $this->happenedOn->startOfDay();

        Contact::query()->whereKey($person->id)->increment('life_event_count');

        Contact::query()
            ->whereKey($person->id)
            ->where(fn (Builder $query): Builder => $query->whereNull('last_life_event_at')->orWhere('last_life_event_at', '<', $day))
            ->update(['last_life_event_at' => $day]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LifeEventCreation,
            vault: $this->vault,
            parameters: [
                'type' => $this->lifeEventType->name,
                'name' => $this->contact->name,
            ],
            loggable: $this->lifeEvent,
        );
    }
}
