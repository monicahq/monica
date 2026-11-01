<?php

declare(strict_types=1);

namespace App\Http\Dav;

use App\Enums\ContactGroupingEnum;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CollectionChanges
{
    /**
     * @param  Closure(Contact): array<int, string>  $urisFor
     * @param  (Closure(Group): string)|null  $groupUriFor  null when the collection holds no group card
     */
    public function __construct(
        private readonly Vault $vault,
        private readonly Closure $urisFor,
        private readonly ?Closure $groupUriFor = null,
    ) {}

    public function token(): string
    {
        $written = $this->timestamp(Contact::query()->withTrashed(), 'updated_at');
        $removed = $this->timestamp(Contact::query()->onlyTrashed(), 'deleted_at');

        if ($this->groupUriFor === null) {
            return (string) max($written, $removed);
        }

        $groupWritten = $this->timestamp(Group::query()->withTrashed(), 'updated_at');
        $groupRemoved = $this->timestamp(Group::query()->onlyTrashed(), 'deleted_at');

        return (string) max($written, $removed, $groupWritten, $groupRemoved);
    }

    /** @return Collection<int, Contact> */
    public function members(): Collection
    {
        return $this->visible()->with(['fieldValues.field', 'avatar'])->get();
    }

    /**
     * A change at the token's own second is handed over again rather than
     * skipped, since two changes can share a second and only one of them would
     * otherwise be told.
     *
     * @return array{syncToken: string, added: array<int, string>, modified: array<int, string>, deleted: array<int, string>}
     */
    public function since(?string $syncToken): array
    {
        $token = $this->token();

        if ($syncToken === null || $syncToken === '') {
            return [
                'syncToken' => $token,
                'added' => [...$this->urisOf($this->visible()->get()), ...$this->groupChanges(null)['added']],
                'modified' => [],
                'deleted' => [],
            ];
        }

        $since = CarbonImmutable::createFromTimestamp((int) $syncToken);
        $groups = $this->groupChanges($since);

        return [
            'syncToken' => $token,
            'added' => [...$this->urisOf($this->visible()->where('created_at', '>=', $since)->get()), ...$groups['added']],
            'modified' => [...$this->urisOf($this->visible()
                ->where('updated_at', '>=', $since)
                ->where('created_at', '<', $since)
                ->get()), ...$groups['modified']],
            'deleted' => [...$this->urisOf($this->gone($since)), ...$groups['deleted']],
        ];
    }

    /** @return array{added: array<int, string>, modified: array<int, string>, deleted: array<int, string>} */
    private function groupChanges(?CarbonImmutable $since): array
    {
        if ($this->groupUriFor === null) {
            return ['added' => [], 'modified' => [], 'deleted' => []];
        }

        if ($since === null) {
            return ['added' => $this->groupUrisOf($this->groups()->get()), 'modified' => [], 'deleted' => []];
        }

        return [
            'added' => $this->groupUrisOf($this->groups()->where('created_at', '>=', $since)->get()),
            'modified' => $this->groupUrisOf($this->changedGroups($since)),
            'deleted' => $this->groupUrisOf($this->groups()->onlyTrashed()->where('deleted_at', '>=', $since)->get()),
        ];
    }

    /** @return Builder<Group> */
    private function groups(): Builder
    {
        return Group::query()->where('vault_id', $this->vault->id);
    }

    /**
     * A group's card changes with the group itself, and also when one of its current members enters or
     * leaves the address book, since the card lists only the members a device can see. A member
     * deleted, restored, archived or made names only is a change to that contact, so a contact changed
     * since the token counts.
     *
     * @return Collection<int, Group>
     */
    private function changedGroups(CarbonImmutable $since): Collection
    {
        return $this->groups()
            ->where('created_at', '<', $since)
            ->where(fn (Builder $query): Builder => $query
                ->where('updated_at', '>=', $since)
                ->orWhereHas('members', fn (Builder $membership): Builder => $membership
                    ->whereNull('left_on')
                    ->whereHas('contact', fn (Builder $contact): Builder => $contact
                        ->withTrashed()
                        ->where(fn (Builder $changed): Builder => $changed
                            ->where('updated_at', '>=', $since)
                            ->orWhere('deleted_at', '>=', $since)))))
            ->get();
    }

    /**
     * @param  Collection<int, Group>  $groups
     * @return array<int, string>
     */
    private function groupUrisOf(Collection $groups): array
    {
        return $groups->map(fn (Group $group): string => ($this->groupUriFor)($group))->values()->all();
    }

    /** @return Builder<Contact> */
    private function visible(): Builder
    {
        return Contact::query()
            ->where('vault_id', $this->vault->id)
            ->inGrouping(ContactGroupingEnum::Everyone);
    }

    /** @return Collection<int, Contact> */
    private function gone(CarbonImmutable $since): Collection
    {
        return Contact::query()
            ->withTrashed()
            ->where('vault_id', $this->vault->id)
            ->where(fn (Builder $query): Builder => $query
                ->where(fn (Builder $trashed): Builder => $trashed
                    ->whereNotNull('deleted_at')
                    ->where('deleted_at', '>=', $since))
                ->orWhere(fn (Builder $hidden): Builder => $hidden
                    ->whereNull('deleted_at')
                    ->where('updated_at', '>=', $since)
                    ->where(fn (Builder $state): Builder => $state
                        ->where('is_archived', true)
                        ->orWhere('is_partial', true))))
            ->get();
    }

    /**
     * @param  Collection<int, Contact>  $contacts
     * @return array<int, string>
     */
    private function urisOf(Collection $contacts): array
    {
        return $contacts
            ->flatMap(fn (Contact $contact): array => ($this->urisFor)($contact))
            ->values()
            ->all();
    }

    /** @param  Builder<Contact>|Builder<Group>  $query */
    private function timestamp(Builder $query, string $column): int
    {
        $latest = $query->where('vault_id', $this->vault->id)->max($column);

        return $latest === null ? 0 : CarbonImmutable::parse($latest)->getTimestamp();
    }
}
