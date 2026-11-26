<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\DatePrecisionEnum;
use App\Enums\TrashItemKindEnum;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\Entity;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\GroupNote;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Relationship;
use App\Models\Reminder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * One record waiting in the trash of a vault: what it is called, where it belonged, how long it
 * still waits, what it was deleted with, and why it cannot come back yet. The screen, the API and
 * the restore all read the same answer here, so the rule is written once.
 */
class TrashItem
{
    public function __construct(
        public readonly TrashItemKindEnum $kind,
        public readonly Contact|Entity|Group|Relationship|LifeEvent|Reminder|GroupMember|GroupNote|GroupFile $record,
    ) {}

    public function uuid(): string
    {
        return $this->record->uuid;
    }

    public function title(): string
    {
        $record = $this->record;

        return match (true) {
            $record instanceof Contact, $record instanceof Entity, $record instanceof Group, $record instanceof LifeEvent, $record instanceof Reminder => $record->name,
            $record instanceof Relationship => __(':name and :other', ['name' => $record->contactA->name, 'other' => $record->contactB->name]),
            $record instanceof GroupMember => __(':name in :group', ['name' => $record->contact->name, 'group' => $record->group->name]),
            $record instanceof GroupNote => Str::limit(trim(strip_tags($record->body)), 80),
            $record instanceof GroupFile => $record->file->original_name,
        };
    }

    /**
     * The word naming what the item is. A record says the name of its own kind, such as Animal.
     */
    public function typeLabel(): string
    {
        if ($this->record instanceof Entity) {
            return $this->record->entityType->name_singular;
        }

        return __($this->kind->label());
    }

    /**
     * Where the item belonged, in a few words.
     */
    public function context(): ?string
    {
        $record = $this->record;

        return match (true) {
            $record instanceof Relationship => $record->relationshipType->name,
            $record instanceof LifeEvent => $this->aboutParticipants($record),
            $record instanceof Reminder => __('About :name', ['name' => $record->remindable->name]),
            $record instanceof GroupNote => __('In :group', ['group' => $record->group->name]),
            $record instanceof GroupFile => __('In :group · :size', ['group' => $record->group->name, 'size' => StorageSize::readable($record->file->size_in_bytes)]),
            default => null,
        };
    }

    public function deletedAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->record->deleted_at);
    }

    public function expiresAt(): CarbonImmutable
    {
        return $this->deletedAt()->addDays((int) config('monica.trash_retention_in_days'));
    }

    public function daysLeft(): int
    {
        return (int) max(0, ceil(now()->diffInDays($this->expiresAt(), false)));
    }

    /**
     * The person or the group this item was deleted with, while that one is still in the trash.
     */
    public function owner(): Contact|Group|null
    {
        if (in_array($this->kind, [TrashItemKindEnum::Contact, TrashItemKindEnum::Entity, TrashItemKindEnum::Group], true)) {
            return null;
        }

        if ($this->record->deleted_with_type === null || $this->record->deleted_with_type === ContactMerge::class) {
            return null;
        }

        $owner = $this->record->deletedWith;

        if (! $owner instanceof Contact && ! $owner instanceof Group) {
            return null;
        }

        return $owner->trashed() ? $owner : null;
    }

    public function canBeRestored(): bool
    {
        return $this->obstacle() === null;
    }

    /**
     * Why the item cannot come back yet, or null when it can.
     */
    public function obstacle(): ?string
    {
        $owner = $this->owner();

        if ($owner !== null) {
            return __('Deleted with :name. Restore :name to bring this back.', ['name' => $owner->name]);
        }

        $record = $this->record;

        if ($record instanceof Entity) {
            return $record->entityType->trashed()
                ? __('Its kind, :name, was deleted from the account settings.', ['name' => $record->entityType->name_singular])
                : null;
        }

        $ends = match (true) {
            $record instanceof Relationship => [$record->contactA, $record->contactB],
            $record instanceof GroupMember => [$record->contact, $record->group],
            $record instanceof GroupNote, $record instanceof GroupFile => [$record->group],
            $record instanceof Reminder => [$record->remindable],
            default => [],
        };

        foreach ($ends as $end) {
            if ($end !== null && $end->trashed()) {
                return $this->missingEnd($end);
            }
        }

        return null;
    }

    private function missingEnd(Contact|Entity|Group $end): string
    {
        if ($end instanceof Group) {
            return __('The group :name is in the trash. Restore the group first.', ['name' => $end->name]);
        }

        return __(':name is in the trash. Restore :name first.', ['name' => $end->name]);
    }

    private function aboutParticipants(LifeEvent $lifeEvent): ?string
    {
        $names = $lifeEvent->participants
            ->map(fn (LifeEventParticipant $participant): string => $participant->participant->name)
            ->values();

        $year = match ($lifeEvent->date_precision) {
            DatePrecisionEnum::Day, DatePrecisionEnum::Month, DatePrecisionEnum::Year => (string) $lifeEvent->happened_on->year,
            DatePrecisionEnum::Decade => __('Between :start and :end', ['start' => intdiv($lifeEvent->happened_on->year, 10) * 10, 'end' => intdiv($lifeEvent->happened_on->year, 10) * 10 + 9]),
            DatePrecisionEnum::Unknown => null,
        };

        $parts = array_filter([
            $names->isEmpty() ? null : __('About :names', ['names' => $names->join(', ')]),
            $year,
        ]);

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
