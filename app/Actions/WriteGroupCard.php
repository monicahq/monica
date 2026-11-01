<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContactGroupingEnum;
use App\Enums\DatePrecisionEnum;
use App\Helpers\TextSanitizer;
use App\Http\Dav\GroupVCard;
use App\Http\Dav\VCardReader;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Sabre\VObject\Property;

/**
 * Apply a group card a device sent: its name, and who is in the group today. A member the card adds
 * joins today and one it leaves out leaves today; nothing else about the group or its history is
 * touched, and no contact is ever created.
 */
class WriteGroupCard
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly ?Group $group,
        private readonly string $davUri,
        private readonly VCardReader $card,
    ) {}

    public function execute(): Group
    {
        $this->validate();

        $name = TextSanitizer::nullablePlainText((string) $this->card->first('FN')?->getValue());

        if ($this->group === null && $name === null) {
            throw new InvalidArgumentException('A group card needs a name.');
        }

        return DB::transaction(function () use ($name): Group {
            $group = $this->write($name);

            $this->writeMembers($group);

            if ($group->dav_uri !== $this->davUri && $this->davUri !== "{$group->uuid}.vcf") {
                $group->dav_uri = $this->davUri;
            }

            $group->touch();

            return $group;
        });
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->group instanceof Group && ($this->group->account_id !== $this->user->account_id || $this->group->vault_id !== $this->vault->id)) {
            throw new ModelNotFoundException('This group is in another vault.');
        }
    }

    private function write(?string $name): Group
    {
        if ($this->group === null) {
            return new CreateGroup(
                user: $this->user,
                vault: $this->vault,
                name: (string) $name,
                davUri: $this->davUri,
            )->execute();
        }

        if ($name === null || $name === $this->group->name) {
            return $this->group;
        }

        return new UpdateGroup(
            user: $this->user,
            vault: $this->vault,
            group: $this->group,
            name: $name,
            description: $this->group->description,
            startedOn: $this->group->started_on,
            startedPrecision: $this->group->started_precision,
            endedOn: $this->group->ended_on,
            endedPrecision: $this->group->ended_precision,
        )->execute();
    }

    /**
     * A device only sees the members the address book holds, so a current member it cannot see
     * (archived, names only) is missing from its card without having been removed, and stays.
     */
    private function writeMembers(Group $group): void
    {
        $named = $this->namedContacts();
        $today = CarbonImmutable::today();

        $current = GroupMember::query()
            ->where('group_id', $group->id)
            ->whereNull('left_on')
            ->with('contact')
            ->whereHas('contact', fn (Builder $contact): Builder => $contact->inGrouping(ContactGroupingEnum::Everyone))
            ->get();

        foreach ($named->whereNotIn('id', $current->pluck('contact_id')) as $contact) {
            new CreateGroupMembership(
                user: $this->user,
                vault: $this->vault,
                group: $group,
                contact: $contact,
                joinedOn: $today,
                joinedPrecision: DatePrecisionEnum::Day,
            )->execute();
        }

        foreach ($current->whereNotIn('contact_id', $named->pluck('id')) as $membership) {
            new UpdateGroupMembership(
                user: $this->user,
                vault: $this->vault,
                membership: $membership,
                role: $membership->role,
                joinedOn: $membership->joined_on,
                joinedPrecision: $membership->joined_precision,
                leftOn: $today,
                leftPrecision: DatePrecisionEnum::Day,
            )->execute();
        }
    }

    /**
     * The contacts of the vault the card names. A reference carries the UID of the contact's own card,
     * which is the contact's uuid unless a device created the contact under a UID of its own, so the
     * stored cards are searched as well and every candidate is checked against the UID it really has.
     *
     * @return Collection<int, Contact>
     */
    private function namedContacts(): Collection
    {
        $references = collect([
            ...$this->card->propertiesOf('MEMBER'),
            ...$this->card->propertiesOf('X-ADDRESSBOOKSERVER-MEMBER'),
        ])
            ->map(fn (Property $member): string => self::bare((string) $member->getValue()))
            ->filter(fn (string $uid): bool => $uid !== '')
            ->unique()
            ->values();

        if ($references->isEmpty()) {
            return new Collection;
        }

        return Contact::query()
            ->where('vault_id', $this->vault->id)
            ->inGrouping(ContactGroupingEnum::Everyone)
            ->where(function (Builder $query) use ($references): void {
                $query->whereIn('uuid', $references->filter(fn (string $uid): bool => Str::isUuid($uid))->all());

                foreach ($references as $uid) {
                    $query->orWhere('vcard', 'like', "%{$uid}%");
                }
            })
            ->get()
            ->filter(fn (Contact $contact): bool => $references->contains(self::bare(GroupVCard::uidOf($contact))))
            ->values();
    }

    private static function bare(string $reference): string
    {
        $reference = mb_trim($reference);

        return str_starts_with(mb_strtolower($reference), 'urn:uuid:') ? mb_substr($reference, 9) : $reference;
    }
}
