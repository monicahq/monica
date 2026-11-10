<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\FlagState;
use App\Helpers\PageSize;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\GroupMember;
use App\Models\LifeEventParticipant;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GroupViewModel
{
    public const int PER_PAGE = 20;

    public const int AVATAR_COUNT = 8;

    /** @var Collection<int, Contact>|null */
    private ?Collection $currentPeople = null;

    /** @var array<int, int>|null */
    private ?array $participation = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
        private readonly int|string $size = self::PER_PAGE,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function name(): string
    {
        return $this->group->name;
    }

    public function description(): ?string
    {
        return $this->group->description;
    }

    public function period(): ?string
    {
        return new GroupPeriod($this->user)->between(
            $this->group->started_on,
            $this->group->started_precision,
            $this->group->ended_on,
            $this->group->ended_precision,
        );
    }

    /**
     * How many people belong to the group today, as the line under its name says it.
     */
    public function headcountLine(): string
    {
        $count = $this->currentPeople()->count();

        if ($count === 0) {
            return __('Nobody belongs to it yet');
        }

        return trans_choice(':count person belongs to it|:count people belong to it', $count);
    }

    /**
     * The names of the first people who belong to the group today, drawn as a row of avatars.
     *
     * @return list<string>
     */
    public function avatarNames(): array
    {
        return $this->currentPeople()
            ->take(self::AVATAR_COUNT)
            ->map(fn (Contact $contact): string => $contact->name)
            ->values()
            ->all();
    }

    public function membersCount(): int
    {
        return GroupMember::query()
            ->where('group_id', $this->group->id)
            ->whereHas('contact')
            ->count();
    }

    /**
     * How many files the group keeps, or null when files are not offered to the account, in which
     * case the tab is absent.
     */
    public function filesCount(): ?int
    {
        if (! $this->offersFiles()) {
            return null;
        }

        return GroupFile::query()->where('group_id', $this->group->id)->count();
    }

    public function offersFiles(): bool
    {
        return FlagState::isOn('file-upload', $this->user->account);
    }

    public function overviewUrl(): string
    {
        return route('groups.show', ['vault' => $this->vault, 'group' => $this->group]);
    }

    public function membersUrl(): string
    {
        return route('groups.members.index', ['vault' => $this->vault, 'group' => $this->group]);
    }

    public function filesUrl(): string
    {
        return route('groups.files.index', ['vault' => $this->vault, 'group' => $this->group]);
    }

    public function indexUrl(): string
    {
        return route('groups.index', $this->vault);
    }

    public function editUrl(): string
    {
        return route('groups.edit', ['vault' => $this->vault, 'group' => $this->group]);
    }

    public function addMemberUrl(): string
    {
        return route('groups.members.new', ['vault' => $this->vault, 'group' => $this->group]);
    }

    public function vCardUrl(): ?string
    {
        if (! FlagState::isOn('vcard-export', $this->user->account)) {
            return null;
        }

        return route('groups.vcard.show', ['vault' => $this->vault, 'group' => $this->group]);
    }

    public function size(): int|string
    {
        return $this->size;
    }

    public function defaultSize(): int
    {
        return self::PER_PAGE;
    }

    /**
     * The people in the group today first, then those who left, the most recent departure first. A
     * deleted contact is left out until it is restored.
     *
     * @return LengthAwarePaginator<int, array{name: string, contactUrl: string, editUrl: string, role: string|null, period: string|null, isCurrent: bool, isOutsideTheGroupDates: bool, participation: string}>
     */
    public function members(): LengthAwarePaginator
    {
        $period = new GroupPeriod($this->user);

        $query = GroupMember::query()
            ->where('group_id', $this->group->id)
            ->whereHas('contact')
            ->with('contact')
            ->orderByRaw('case when left_on is null then 0 else 1 end')
            ->orderByDesc('left_on')
            ->orderBy('id');

        return PageSize::paginate($query, $this->size)
            ->through(fn (GroupMember $membership): array => [
                'name' => $membership->contact->name,
                'contactUrl' => route('contacts.show', ['vault' => $this->vault, 'contact' => $membership->contact]),
                'editUrl' => route('groups.members.edit', ['vault' => $this->vault, 'group' => $this->group, 'membership' => $membership]),
                'role' => $membership->role,
                'period' => $period->between($membership->joined_on, $membership->joined_precision, $membership->left_on, $membership->left_precision),
                'isCurrent' => $membership->isCurrent(),
                'isOutsideTheGroupDates' => $this->isOutsideTheGroupDates($membership),
                'participation' => $this->participationOf($membership->contact_id),
            ]);
    }

    /**
     * The people in the group today, in the order they joined. A deleted contact is left out.
     *
     * @return Collection<int, Contact>
     */
    private function currentPeople(): Collection
    {
        return $this->currentPeople ??= GroupMember::query()
            ->where('group_id', $this->group->id)
            ->whereNull('left_on')
            ->whereHas('contact')
            ->with('contact')
            ->orderBy('id')
            ->get()
            ->map(fn (GroupMember $membership): Contact => $membership->contact)
            ->unique('id')
            ->values();
    }

    /**
     * How many of the activities recorded on the group somebody took part in.
     */
    private function participationOf(int $contactId): string
    {
        $this->participation ??= LifeEventParticipant::query()
            ->join('life_events', 'life_events.id', '=', 'life_event_participants.life_event_id')
            ->where('life_events.group_id', $this->group->id)
            ->whereNull('life_events.deleted_at')
            ->where('life_event_participants.participant_type', new Contact()->getMorphClass())
            ->groupBy('life_event_participants.participant_id')
            ->selectRaw('life_event_participants.participant_id as contact_id, count(*) as attended')
            ->pluck('attended', 'contact_id')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();

        $count = $this->participation[$contactId] ?? 0;

        if ($count === 0) {
            return __('No activities yet');
        }

        return trans_choice('At :count activity|At :count activities', $count);
    }

    /**
     * Whether the membership starts before the group began or ends after it ended. It is accepted,
     * since either date may be the wrong one, and pointed out.
     */
    private function isOutsideTheGroupDates(GroupMember $membership): bool
    {
        $startedOn = $this->group->started_on;
        $endedOn = $this->group->ended_on;

        if ($startedOn !== null && $membership->joined_on !== null && $membership->joined_on->lt($startedOn)) {
            return true;
        }

        return $endedOn !== null && $membership->left_on !== null && $membership->left_on->gt($endedOn);
    }
}
