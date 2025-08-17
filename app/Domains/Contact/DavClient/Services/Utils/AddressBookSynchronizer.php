<?php

namespace App\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\Dav\VCardResource;
use App\Domains\Contact\DavClient\Jobs\DeleteVCard;
use App\Domains\Contact\DavClient\Jobs\PushVCard;
use App\Domains\Contact\DavClient\Services\UpdateSubscriptionLocalSyncToken;
use App\Domains\Contact\DavClient\Services\Utils\Dav\DavClient;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDeleteDto;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto;
use App\Domains\Contact\DavClient\Services\Utils\Traits\HasCapability;
use App\Domains\Contact\DavClient\Services\Utils\Traits\HasSubscription;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AddressBookSynchronizer
{
    use HasCapability, HasSubscription;

    private DavClient $client;

    /**
     * Sync the address book.
     */
    public function execute(bool $force = false): string
    {
        $this->client = $this->subscription->getClient();

        $updateSyncToken = new UpdateSubscriptionLocalSyncToken([
            'addressbook_subscription_id' => $this->subscription->id,
        ]);

        $jobs = $force
            ? $this->forcesync()
            : $this->sync();

        $batch = Bus::batch($jobs);

        if ($this->subscription->isWayPush) {
            $batch = $batch->then(fn () => $updateSyncToken->handle());
        }

        $batch = $batch->allowFailures()
            ->onQueue('high')
            ->dispatch();

        return $batch->id;
    }

    private function getLocalChanges(): Collection
    {
        $localChanges = $this->backend()->getChangesForAddressBook($this->subscription->vault_id, (string) $this->subscription->sync_token_id, 1);

        return Collection::wrap($localChanges)
            ->map(fn ($changes): Collection => Collection::wrap($changes));
    }

    /**
     * Sync the address book.
     */
    private function sync(): Collection
    {
        // Get distant changes to sync
        $changes = $this->getDistantChanges();

        // Get distant contacts
        $jobs = collect();
        if ($this->subscription->isWayGet) {
            $jobs = app(PrepareJobsContactUpdater::class)
                ->withSubscription($this->subscription)
                ->execute($changes);
        }

        if ($this->subscription->isWayPush) {
            // Get changes to sync
            $localChanges = $this->getLocalChanges();

            $pushes = app(PrepareJobsContactPush::class)
                ->withSubscription($this->subscription)
                ->execute($localChanges, $changes);
            $this->logs($pushes);

            $jobs = $jobs->merge($pushes);
        }

        return $jobs;
    }

    /**
     * Sync the address book.
     */
    private function forcesync(): Collection
    {
        // Get current list of contacts
        /** @var Collection<array-key,VCardResource> $localContacts */
        $localContacts = $this->backend()->getObjects($this->subscription->vault_id);
        $localUuids = $localContacts->pluck('id');

        // Get distant changes to sync
        $distContacts = $this->getAllContactsEtag();

        // Get missed contacts
        $missed = $distContacts->reject(fn (ContactDto $contact): bool => $localUuids->contains($this->backend()->getUuid($contact->uri)));

        $jobs = collect();
        if ($this->subscription->isWayGet) {
            $jobs = app(PrepareJobsContactUpdater::class)
                ->withSubscription($this->subscription)
                ->execute($missed);
        }

        if ($this->subscription->isWayPush) {
            // Get changes to sync
            $localChanges = $this->getLocalChanges();

            $pushes = app(PrepareJobsContactPushMissed::class)
                ->withSubscription($this->subscription)
                ->execute($localChanges, $distContacts, $localContacts);
            $this->logs($pushes);

            $jobs = $jobs->merge($pushes);
        }

        return $jobs;
    }

    /**
     * Filter contacts to only return vcards type and new contacts or contacts with matching etags.
     */
    private function filterDistantContacts(mixed $contact, string $href): bool
    {
        // only return vcards
        if (! is_array($contact) || ! Str::contains(Arr::get($contact, 'properties.200.{DAV:}getcontenttype'), 'text/vcard')) {
            return false;
        }

        // only new contact or contact with etag that match
        $card = $this->backend()->getCard($this->subscription->vault_id, $href);

        return $card === false || $card['etag'] !== Arr::get($contact, 'properties.200.{DAV:}getetag');
    }

    /**
     * Get distant changes to sync.
     */
    private function getDistantChanges(): Collection
    {
        $etags = $this->getDistantEtags();
        $data = collect($etags);

        $updated = $data->filter(fn ($contact, $href): bool => $this->filterDistantContacts($contact, $href))
            ->map(fn (array $contact, string $href): ContactDto => new ContactDto($href, Arr::get($contact, 'properties.200.{DAV:}getetag')));
        $deleted = $data->filter(fn ($contact): bool => is_array($contact) && $contact['status'] === '404')
            ->map(fn (array $contact, string $href): ContactDto => new ContactDeleteDto($href));

        return $updated->merge($deleted); // @phpstan-ignore argument.type
    }

    /**
     * Get all contacts etag.
     */
    private function getAllContactsEtag(): Collection
    {
        if (! $this->hasCapability('addressbookQuery')) {
            return collect();
        }

        $query = $this->client->addressbookQuery('{DAV:}getetag');

        $data = collect($query);

        $updated = $data->filter(fn ($contact): bool => is_array($contact) && $contact['status'] === '200')
            ->map(fn (array $contact, string $href): ContactDto => new ContactDto($href, Arr::get($contact, 'properties.200.{DAV:}getetag')));
        $deleted = $data->filter(fn ($contact): bool => is_array($contact) && $contact['status'] === '404')
            ->map(fn (array $contact, string $href): ContactDto => new ContactDeleteDto($href));

        return $updated->merge($deleted); // @phpstan-ignore argument.type
    }

    /**
     * Get refreshed etags.
     */
    private function getDistantEtags(): array
    {
        return $this->hasCapability('syncCollection')

            // With sync-collection
            ? $this->callSyncCollectionWhenNeeded()

            // With PROPFIND
            : $this->client->propFind([
                '{DAV:}getcontenttype',
                '{DAV:}getetag',
            ], 1);
    }

    /**
     * Make sync-collection request if sync-token has changed.
     */
    private function callSyncCollectionWhenNeeded(): array
    {
        // get the current distant syncToken
        $currentSyncToken = $this->client->getProperty('{DAV:}sync-token');

        if (($this->subscription->distant_sync_token ?? '') === $currentSyncToken) {
            // no change at all
            return [];
        }

        return $this->callSyncCollection();
    }

    /**
     * Make sync-collection request.
     */
    private function callSyncCollection(): array
    {
        $syncToken = $this->subscription->distant_sync_token ?? '';

        // get sync
        try {
            $collection = $this->client->syncCollection([
                '{DAV:}getcontenttype',
                '{DAV:}getetag',
            ], $syncToken);

            // save the new syncToken as current one
            if ($newSyncToken = Arr::get($collection, 'synctoken')) {
                $this->subscription->distant_sync_token = $newSyncToken;
                $this->subscription->save();
            }
        } catch (RequestException $e) {
            Log::channel('database')->error(__CLASS__.' '.__FUNCTION__.':'.$e->getMessage(), [$e]);
            $collection = [];
            $this->subscription->distant_sync_token = null;
            $this->subscription->save();
        }

        return $collection;
    }

    private function logs(Collection $jobs): void
    {
        $counts = $jobs->countBy(fn ($job): string => $job::class);

        if (($updated = $counts->get(PushVCard::class, 0)) > 0) {
            Log::channel('database')->info("Update or create $updated card(s) to distant server...");
        }
        if (($deleted = $counts->get(DeleteVCard::class, 0)) > 0) {
            Log::channel('database')->info("Delete $deleted card(s) to distant server...");
        }
    }
}
