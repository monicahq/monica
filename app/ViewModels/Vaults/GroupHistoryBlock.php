<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Helpers\AvailableLocales;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupNote;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Section;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Collection;

class GroupHistoryBlock
{
    public const int PER_PAGE = 20;

    /** @var Collection<int, array<string, mixed>>|null */
    private ?Collection $entries = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
        private readonly int $page = 1,
        private readonly ?int $justAddedLifeEventId = null,
        private readonly ?int $editingNoteId = null,
    ) {}

    /**
     * What the group did together and what was noted about it, newest first. An activity is dated by
     * when it happened and a note by the day it was written.
     *
     * @return list<array<string, mixed>>
     */
    public function entries(): array
    {
        return $this->merged()->take($this->page * self::PER_PAGE)->values()->all();
    }

    public function holdsNothing(): bool
    {
        return $this->merged()->isEmpty();
    }

    public function nextPageUrl(): ?string
    {
        if ($this->merged()->count() <= $this->page * self::PER_PAGE) {
            return null;
        }

        return route('groups.show', ['vault' => $this->vault, 'group' => $this->group, 'page' => $this->page + 1]);
    }

    /**
     * Enough of both sources to fill every page asked for, plus one to know whether there is more.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function merged(): Collection
    {
        if ($this->entries instanceof Collection) {
            return $this->entries;
        }

        $limit = $this->page * self::PER_PAGE + 1;
        $headcount = $this->headcount();

        $activities = LifeEvent::query()
            ->where('group_id', $this->group->id)
            ->with(['lifeEventType', 'participants.participant'])
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (LifeEvent $lifeEvent): array => $this->activity($lifeEvent, $headcount));

        $notes = GroupNote::query()
            ->where('group_id', $this->group->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (GroupNote $note): array => $this->note($note));

        return $this->entries = $activities
            ->concat($notes)
            ->sortByDesc(fn (array $entry): string => $entry['sortKey'])
            ->values();
    }

    /** @return array<string, mixed> */
    private function activity(LifeEvent $lifeEvent, int $headcount): array
    {
        $people = $lifeEvent->participants
            ->map(fn (LifeEventParticipant $participant): mixed => $participant->participant)
            ->filter(fn (mixed $participant): bool => $participant instanceof Contact)
            ->values();

        $firstNames = $people->map(fn (Contact $contact): string => $contact->first_name ?? $contact->name);
        $names = $firstNames->count() > 1
            ? __(':names and :last', ['names' => $firstNames->slice(0, -1)->implode(__(', ')), 'last' => $firstNames->last()])
            : (string) $firstNames->first();

        $editor = $people->first(fn (Contact $contact): bool => ! $contact->is_partial);
        $icon = $lifeEvent->lifeEventType->icon;

        return [
            'kind' => 'activity',
            'id' => $lifeEvent->id,
            'icon' => in_array($icon, Section::ICONS, true) ? $icon : Section::DEFAULT_ICON,
            'kindLabel' => $lifeEvent->lifeEventType->name,
            'when' => DateFormatEnum::write($lifeEvent->happened_on, $this->dateFormat()),
            'sortKey' => $lifeEvent->happened_on->toDateString().' 23:59:59 '.mb_str_pad((string) $lifeEvent->id, 12, '0', STR_PAD_LEFT),
            'title' => $lifeEvent->title,
            'body' => $lifeEvent->narrative,
            'participants' => $people->map(fn (Contact $contact): string => $contact->name)->all(),
            'participantLine' => $people->isEmpty()
                ? null
                : trans_choice(':count of :total was there: :names|:count of :total were there: :names', $people->count(), [
                    'total' => max($headcount, $people->count()),
                    'names' => $names,
                ]),
            'editUrl' => $editor instanceof Contact
                ? route('contacts.lifeEvents.edit', ['vault' => $this->vault, 'contact' => $editor, 'lifeEvent' => $lifeEvent])
                : null,
            'isJustAdded' => $lifeEvent->id === $this->justAddedLifeEventId,
        ];
    }

    /** @return array<string, mixed> */
    private function note(GroupNote $note): array
    {
        $written = $note->created_at->setTimezone($this->user->timezone ?? 'UTC');

        return [
            'kind' => 'note',
            'id' => $note->id,
            'icon' => 'note',
            'kindLabel' => __('Note'),
            'when' => DateFormatEnum::write($written, $this->dateFormat()),
            'sortKey' => $written->toDateString().' '.$written->format('H:i:s').' '.mb_str_pad((string) $note->id, 12, '0', STR_PAD_LEFT),
            'title' => null,
            'body' => $note->body,
            'editUrl' => route('groups.notes.edit', ['vault' => $this->vault, 'group' => $this->group, 'groupNote' => $note]),
            'updateUrl' => route('groups.notes.update', ['vault' => $this->vault, 'group' => $this->group, 'groupNote' => $note]),
            'destroyUrl' => route('groups.notes.destroy', ['vault' => $this->vault, 'group' => $this->group, 'groupNote' => $note]),
            'cancelUrl' => route('groups.show', ['vault' => $this->vault, 'group' => $this->group]),
            'isEditing' => $note->id === $this->editingNoteId,
        ];
    }

    private function headcount(): int
    {
        return GroupMember::query()
            ->where('group_id', $this->group->id)
            ->whereNull('left_on')
            ->whereHas('contact')
            ->distinct()
            ->count('contact_id');
    }

    private function dateFormat(): string
    {
        return $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];
    }
}
