<?php

declare(strict_types=1);

namespace App\Http\Dav;

use App\Actions\DestroyContact;
use App\Actions\ImportVCard;
use App\Enums\ContactGroupingEnum;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Override;
use Sabre\CardDAV\Backend\BackendInterface;
use Sabre\CardDAV\Backend\SyncSupport;
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
        return $this->members($this->vault($addressBookId))
            ->map(fn (Contact $contact): array => $this->row($contact))
            ->all();
    }

    /** @return array<string, mixed>|false */
    #[Override]
    public function getCard($addressBookId, $cardUri)
    {
        $contact = $this->contact($this->vault($addressBookId), (string) $cardUri);

        if ($contact === null) {
            return false;
        }

        return $this->row($contact, withCard: true);
    }

    /** @return array<int, array<string, mixed>> */
    #[Override]
    public function getMultipleCards($addressBookId, array $uris): array
    {
        $vault = $this->vault($addressBookId);

        return $this->members($vault)
            ->filter(fn (Contact $contact): bool => in_array($this->uriOf($contact), $uris, true))
            ->map(fn (Contact $contact): array => $this->row($contact, withCard: true))
            ->values()
            ->all();
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

        new ImportVCard(
            user: $this->user,
            vault: $vault,
            card: (string) $cardData,
            reachesSensitiveFields: $this->reachesSensitiveFields,
            contact: $this->contactNamedBy($vault, (string) $cardData),
            davUri: (string) $cardUri,
        )->execute();

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

        if ($uid === '') {
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
        $contact = $this->contact($this->vault($addressBookId), (string) $cardUri);

        if ($contact === null) {
            return false;
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
            $row['carddata'] = new VCardMerger($this->reachesSensitiveFields)->merge($contact);
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
        return $this->members($vault)
            ->first(fn (Contact $contact): bool => $this->uriOf($contact) === $cardUri);
    }

    /** @return Collection<int, Contact> */
    private function members(Vault $vault): Collection
    {
        return Contact::query()
            ->where('vault_id', $vault->id)
            ->inGrouping(ContactGroupingEnum::Everyone)
            ->with(['avatar', 'structureVersion'])
            ->get();
    }

    private function changesIn(Vault $vault): CollectionChanges
    {
        return new CollectionChanges($vault, fn (Contact $contact): array => [$this->uriOf($contact)]);
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
