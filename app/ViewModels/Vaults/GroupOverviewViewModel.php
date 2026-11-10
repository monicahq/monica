<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Group;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use App\Models\Vault;

class GroupOverviewViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
        private readonly int $page = 1,
        private readonly ?int $justAddedLifeEventId = null,
        private readonly ?int $editingNoteId = null,
    ) {}

    public function header(): GroupViewModel
    {
        return once(fn (): GroupViewModel => new GroupViewModel(user: $this->user, vault: $this->vault, group: $this->group));
    }

    public function history(): GroupHistoryBlock
    {
        return once(fn (): GroupHistoryBlock => new GroupHistoryBlock(
            user: $this->user,
            vault: $this->vault,
            group: $this->group,
            page: $this->page,
            justAddedLifeEventId: $this->justAddedLifeEventId,
            editingNoteId: $this->editingNoteId,
        ));
    }

    public function reminders(): GroupRemindersBlock
    {
        return once(fn (): GroupRemindersBlock => new GroupRemindersBlock(user: $this->user, vault: $this->vault, group: $this->group));
    }

    /**
     * The files kept with the group, or null when files are not offered to the account.
     */
    public function files(): ?GroupFilesBlock
    {
        if (! $this->header()->offersFiles()) {
            return null;
        }

        return once(fn (): GroupFilesBlock => new GroupFilesBlock(user: $this->user, vault: $this->vault, group: $this->group));
    }

    /**
     * The one sentence a group holding files says while files are not offered to the account, so
     * nobody thinks they are gone. Null when files are offered, or when the group holds none.
     */
    public function keptFilesLine(): ?string
    {
        if ($this->header()->offersFiles()) {
            return null;
        }

        if (! GroupFile::query()->where('group_id', $this->group->id)->exists()) {
            return null;
        }

        return __('The files kept with this group are still there. They show again once files are offered to your account.');
    }

    /**
     * The life event types somebody may record an activity as, in the order the type picker shows
     * them, the first one chosen unless the form came back refused.
     *
     * @return list<array{id: int, name: string, category: string}>
     */
    public function types(): array
    {
        return once(fn (): array => LifeEventCategory::query()
            ->where('account_id', $this->user->account_id)
            ->where('is_active', true)
            ->with(['lifeEventTypes' => fn ($query) => $query->where('is_active', true)->orderBy('position')])
            ->orderBy('position')
            ->get()
            ->flatMap(fn (LifeEventCategory $category): array => $category->lifeEventTypes
                ->map(fn (LifeEventType $type): array => ['id' => $type->id, 'name' => $type->name, 'category' => $category->name])
                ->all())
            ->values()
            ->all());
    }

    public function chosenTypeId(): ?int
    {
        $old = old('life_event_type_id');

        if ($old !== null) {
            return (int) $old;
        }

        return $this->types()[0]['id'] ?? null;
    }

    public function today(): string
    {
        return now($this->user->timezone)->toDateString();
    }

    /**
     * Who may be ticked as having been there: the people in the group today, ticked, then those who
     * left, not ticked. A form that came back refused keeps what was ticked.
     *
     * @return list<array{id: int, name: string, isTicked: bool, isCurrent: bool}>
     */
    public function participants(): array
    {
        $old = old('participants');
        $ticked = is_array($old) ? array_map(intval(...), $old) : null;

        return GroupMember::query()
            ->where('group_id', $this->group->id)
            ->whereHas('contact')
            ->with('contact')
            ->orderByRaw('case when left_on is null then 0 else 1 end')
            ->orderBy('id')
            ->get()
            ->unique('contact_id')
            ->map(fn (GroupMember $membership): array => [
                'id' => $membership->contact_id,
                'name' => $membership->contact->name,
                'isTicked' => $ticked === null ? $membership->isCurrent() : in_array($membership->contact_id, $ticked, true),
                'isCurrent' => $membership->isCurrent(),
            ])
            ->values()
            ->all();
    }

    /**
     * Which kind the writing box opens on: the one a refused form was written as, else an activity.
     */
    public function kind(): string
    {
        return old('kind') === 'note' ? 'note' : 'activity';
    }

    public function lifeEventUrl(): string
    {
        return route('groups.lifeEvents.create', ['vault' => $this->vault, 'group' => $this->group]);
    }

    public function noteUrl(): string
    {
        return route('groups.notes.create', ['vault' => $this->vault, 'group' => $this->group]);
    }

    public function addMemberUrl(): string
    {
        return route('groups.members.new', ['vault' => $this->vault, 'group' => $this->group]);
    }
}
