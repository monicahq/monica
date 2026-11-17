<?php

declare(strict_types=1);

namespace App\Http\Dav;

use App\Actions\DestroyContact;
use App\Actions\DestroyGroup;
use App\Actions\ImportVCard;
use App\Actions\WriteGroupCard;
use App\Enums\ContactGroupingEnum;
use App\Exceptions\VaultHasNoContactDefault;
use App\Helpers\FlagState;
use App\Models\Contact;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Override;
use Sabre\CardDAV\Backend\BackendInterface;
use Sabre\CardDAV\Backend\SyncSupport;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\PropPatch;

class AddressBookBackend implements BackendInterface, SyncSupport
{
    public function __construct(
        private readonly User $user,
        private readonly bool $reachesSensitiveFields = false,
    ) {}

    /** @return array<int, array<string, mixed>> */
    #[Override]
    public function getAddressBooksForUser($principalUri): array
    {
        return $this->vaults()
            ->map(fn (Vault $vault): array => [
                'id' => $vault->id,
                'uri' => $vault->uuid,
                'principaluri' => $principalUri,
                '{DAV:}displayname' => $vault->name,
                '{http://calendarserver.org/ns/}getctag' => $this->changesIn($vault)->token(),
                '{http://sabredav.org/ns}sync-token' => $this->changesIn($vault)->token(),
            ])
            ->all();
    }

    #[Override]
    public function updateAddressBook($addressBookId, PropPatch $propPatch): void
    {
        $propPatch->handleRemaining(fn (): bool => false);
    }

    #[Override]
    public function createAddressBook($principalUri, $url, array $properties): void
    {
        throw new Forbidden('An address book is a vault, and a vault is made in the application.');
    }

    #[Override]
    public function deleteAddressBook($addressBookId): void
    {
        throw new Forbidden('An address book is a vault, and a vault is deleted in the application.');
    }

    /** @return array<int, array<string, mixed>> */
    #[Override]
    public function getCards($addressBookId): array
    {
        $vault = $this->vault($addressBookId);

        return [
            ...$this->members($vault)->map(fn (Contact $contact): array => $this->row($contact))->all(),
            ...$this->groupsAt($vault)->get()->map(fn (Group $group): array => $this->groupRow($group))->all(),
        ];
    }

    /** @return array<string, mixed>|false */
    #[Override]
    public function getCard($addressBookId, $cardUri)
    {
        $vault = $this->vault($addressBookId);
        $contact = $this->contact($vault, (string) $cardUri);

        if ($contact instanceof Contact) {
            return $this->row($contact, withCard: true);
        }

        $group = $this->group($vault, (string) $cardUri);

        if ($group === null) {
            return false;
        }

        return $this->groupRow($group, withCard: true);
    }

    /** @return array<int, array<string, mixed>> */
    #[Override]
    public function getMultipleCards($addressBookId, array $uris): array
    {
        $vault = $this->vault($addressBookId);
        $cardUris = array_map(strval(...), $uris);

        return [
            ...$this->membersAt($vault, $cardUris)->get()->map(fn (Contact $contact): array => $this->row($contact, withCard: true))->all(),
            ...$this->groupsAt($vault, $cardUris)->get()->map(fn (Group $group): array => $this->groupRow($group, withCard: true))->all(),
        ];
    }

    /**
     * No ETag comes back from a write. The card a device sends is kept and read back with the
     * record merged over it, so what the server holds is never byte for byte what arrived, and a
     * device told otherwise would sit on a card that differs from ours.
     */
    #[Override]
    public function createCard($addressBookId, $cardUri, $cardData): ?string
    {
        $vault = $this->vault($addressBookId);
        $card = new VCardReader((string) $cardData);

        if ($this->isGroupCard($card)) {
            $this->writeGroup($vault, $this->groupNamedBy($vault, $card), (string) $cardUri, $card);

            return null;
        }

        try {
            new ImportVCard(
                user: $this->user,
                vault: $vault,
                card: (string) $cardData,
                reachesSensitiveFields: $this->reachesSensitiveFields,
                contact: $this->contactNamedBy($vault, (string) $cardData),
                davUri: (string) $cardUri,
            )->execute();
        } catch (VaultHasNoContactDefault $exception) {
            throw new Forbidden('This vault has no template for new contacts to start from. Choose one in the vault settings.', 0, $exception);
        }

        return null;
    }

    /**
     * A card exported from here and loaded onto a device comes back filed under a name the device
     * chose, which no contact answers to, so it would otherwise arrive as a second person. The
     * identifier it carries says who it is. The vault is part of the query rather than a check
     * afterwards, so an identifier held in another vault is never read, let alone written.
     */
    private function contactNamedBy(Vault $vault, string $cardData): ?Contact
    {
        $uid = (string) (new VCardReader($cardData)->first('UID')?->getValue() ?? '');

        if (! Str::isUuid($uid)) {
            return null;
        }

        return Contact::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uid)
            ->first();
    }

    #[Override]
    public function updateCard($addressBookId, $cardUri, $cardData): ?string
    {
        $vault = $this->vault($addressBookId);
        $card = new VCardReader((string) $cardData);

        if ($this->isGroupCard($card)) {
            $group = $this->group($vault, (string) $cardUri);

            if ($group === null) {
                throw new NotFound('No group card of yours lives there.');
            }

            $this->writeGroup($vault, $group, (string) $cardUri, $card);

            return null;
        }

        new ImportVCard(
            user: $this->user,
            vault: $vault,
            card: (string) $cardData,
            reachesSensitiveFields: $this->reachesSensitiveFields,
            contact: $this->contactOrFail($vault, (string) $cardUri),
            davUri: (string) $cardUri,
        )->execute();

        return null;
    }

    #[Override]
    public function deleteCard($addressBookId, $cardUri): bool
    {
        $vault = $this->vault($addressBookId);
        $contact = $this->contact($vault, (string) $cardUri);

        if ($contact === null) {
            return $this->deleteGroup($vault, (string) $cardUri);
        }

        try {
            new DestroyContact(user: $this->user, contact: $contact)->execute();
        } catch (AuthorizationException $exception) {
            throw new Forbidden('The contact representing you cannot be removed from a device.', 0, $exception);
        }

        return true;
    }

    private function contactOrFail(Vault $vault, string $cardUri): Contact
    {
        $contact = $this->contact($vault, $cardUri);

        if ($contact === null) {
            throw new NotFound('No card of yours lives there.');
        }

        return $contact;
    }

    /**
     * A group card is told apart by its kind, in either the RFC 6350 form or Apple's, and never
     * reaches ImportVCard, so it cannot become a contact. `CATEGORIES` on a contact card is not read as
     * a membership at all.
     */
    private function isGroupCard(VCardReader $card): bool
    {
        $kinds = [
            mb_strtolower((string) $card->first('KIND')?->getValue()),
            mb_strtolower((string) $card->first('X-ADDRESSBOOKSERVER-KIND')?->getValue()),
        ];

        return in_array('group', $kinds, true);
    }

    private function writeGroup(Vault $vault, ?Group $group, string $cardUri, VCardReader $card): void
    {
        if (! $this->servesGroups()) {
            throw new Forbidden('This address book does not hold groups.');
        }

        try {
            new WriteGroupCard(
                user: $this->user,
                vault: $vault,
                group: $group,
                davUri: $cardUri,
                card: $card,
            )->execute();
        } catch (InvalidArgumentException $exception) {
            throw new BadRequest($exception->getMessage(), 0, $exception);
        }
    }

    private function deleteGroup(Vault $vault, string $cardUri): bool
    {
        $group = $this->group($vault, $cardUri);

        if ($group === null) {
            return false;
        }

        new DestroyGroup(user: $this->user, vault: $vault, group: $group)->execute();

        return true;
    }

    /** A group card exported from here and saved again under another name names the group it came from. */
    private function groupNamedBy(Vault $vault, VCardReader $card): ?Group
    {
        $uid = (string) ($card->first('UID')?->getValue() ?? '');

        if (! Str::isUuid($uid)) {
            return null;
        }

        return Group::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uid)
            ->first();
    }

    /** @return array{syncToken: string, added: array<int, string>, modified: array<int, string>, deleted: array<int, string>}|null */
    #[Override]
    public function getChangesForAddressBook($addressBookId, $syncToken, $syncLevel, $limit = null): ?array
    {
        return $this->changesIn($this->vault($addressBookId))->since($syncToken);
    }

    public function uriOf(Contact $contact): string
    {
        return $contact->dav_uri ?? $contact->uuid.'.vcf';
    }

    public function uriOfGroup(Group $group): string
    {
        return $group->dav_uri ?? $group->uuid.'.vcf';
    }

    /**
     * The etag of a group card is the card itself, since the card changes with members entering or
     * leaving the address book as well as with the group.
     *
     * @return array<string, mixed>
     */
    private function groupRow(Group $group, bool $withCard = false): array
    {
        $card = new GroupVCard($group)->serialize();

        $row = [
            'id' => $group->id,
            'uri' => $this->uriOfGroup($group),
            'lastmodified' => $group->updated_at?->getTimestamp(),
            'etag' => '"'.md5($card).'"',
        ];

        if ($withCard) {
            $row['carddata'] = $card;
            $row['size'] = strlen($card);
        }

        return $row;
    }

    /** @return array<string, mixed> */
    private function row(Contact $contact, bool $withCard = false): array
    {
        $row = [
            'id' => $contact->id,
            'uri' => $this->uriOf($contact),
            'lastmodified' => $contact->updated_at?->getTimestamp(),
            'etag' => $this->etagOf($contact),
        ];

        if ($withCard) {
            $row['carddata'] = new VCardMerger($this->reachesSensitiveFields, preloaded: true)->merge($contact);
            $row['size'] = strlen($row['carddata']);
        }

        return $row;
    }

    private function etagOf(Contact $contact): string
    {
        return '"'.md5(implode('|', [
            (string) $contact->updated_at?->getTimestamp(),
            app()->getLocale(),
            $this->reachesSensitiveFields ? 'sensitive' : 'plain',
        ])).'"';
    }

    private function contact(Vault $vault, string $cardUri): ?Contact
    {
        return $this->membersAt($vault, [$cardUri])->first();
    }

    /** @return Collection<int, Contact> */
    private function members(Vault $vault): Collection
    {
        return $this->membersQuery($vault)->get();
    }

    /**
     * The members living at the addresses given, found by the database rather than by reading the
     * whole vault. An address is the name a device chose, or the uuid for a card made here, which
     * only answers when no device ever named it otherwise, mirroring uriOf().
     *
     * @param  array<int, string>  $cardUris
     * @return Builder<Contact>
     */
    private function membersAt(Vault $vault, array $cardUris): Builder
    {
        $uuids = array_values(array_map(
            fn (string $uri): string => substr($uri, 0, -4),
            array_filter($cardUris, fn (string $uri): bool => str_ends_with($uri, '.vcf') && Str::isUuid(substr($uri, 0, -4))),
        ));

        return $this->membersQuery($vault)
            ->with(VCardMerger::relations())
            ->where(fn (Builder $query): Builder => $query
                ->whereIn('dav_uri', $cardUris)
                ->orWhere(fn (Builder $query): Builder => $query
                    ->whereNull('dav_uri')
                    ->whereIn('uuid', $uuids)));
    }

    /** @return Builder<Contact> */
    private function membersQuery(Vault $vault): Builder
    {
        return Contact::query()
            ->where('vault_id', $vault->id)
            ->inGrouping(ContactGroupingEnum::Everyone)
            ->with(['avatar', 'structureVersion']);
    }

    private function group(Vault $vault, string $cardUri): ?Group
    {
        return $this->groupsAt($vault, [$cardUri])->first();
    }

    /**
     * The groups of the vault, or those living at the addresses given, found as membersAt() finds
     * contacts. None at all while groups are not released to the account.
     *
     * @param  array<int, string>|null  $cardUris
     * @return Builder<Group>
     */
    private function groupsAt(Vault $vault, ?array $cardUris = null): Builder
    {
        $query = Group::query()->where('vault_id', $vault->id);

        if (! $this->servesGroups()) {
            return $query->whereRaw('1 = 0');
        }

        if ($cardUris === null) {
            return $query;
        }

        $uuids = array_values(array_map(
            fn (string $uri): string => substr($uri, 0, -4),
            array_filter($cardUris, fn (string $uri): bool => str_ends_with($uri, '.vcf') && Str::isUuid(substr($uri, 0, -4))),
        ));

        return $query->where(fn (Builder $query): Builder => $query
            ->whereIn('dav_uri', $cardUris)
            ->orWhere(fn (Builder $query): Builder => $query
                ->whereNull('dav_uri')
                ->whereIn('uuid', $uuids)));
    }

    private function servesGroups(): bool
    {
        return FlagState::isOn('groups', $this->user->account);
    }

    private function changesIn(Vault $vault): CollectionChanges
    {
        return new CollectionChanges(
            $vault,
            fn (Contact $contact): array => [$this->uriOf($contact)],
            $this->servesGroups() ? $this->uriOfGroup(...) : null,
        );
    }

    private function vault(mixed $addressBookId): Vault
    {
        $vault = $this->vaults()->firstWhere('id', $addressBookId);

        if ($vault === null) {
            throw new NotFound('No address book of yours lives there.');
        }

        return $vault;
    }

    /** @return Collection<int, Vault> */
    private function vaults(): Collection
    {
        return Vault::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('name')
            ->get();
    }
}
