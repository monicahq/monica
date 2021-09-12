<?php

namespace App\Services\DavClient\Utils;

use GuzzleHttp\Psr7\Request;
use App\Models\Contact\Contact;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use IlluminateAgnostic\Collection\Support\Arr;
use App\Services\DavClient\Utils\Model\SyncDto;
use App\Services\DavClient\Utils\Traits\HasCapability;

class AddressBookContactsPusher
{
    use HasCapability;

    /**
     * @var SyncDto
     */
    private $sync;

    /**
     * Push contacts to the distant server.
     *
     * @param  Collection  $changes
     * @param  array|null  $localChanges
     * @param  Collection|null  $distContacts
     * @param  Collection|null  $localContacts
     */
    public function pushContacts(SyncDto $sync, Collection $changes, ?array $localChanges, ?Collection $distContacts = null, ?Collection $localContacts = null): void
    {
        $this->sync = $sync;

        $requests = $this->preparePushChanges($changes, $localChanges);

        if ($distContacts !== null && $localContacts !== null) {
            $requestsMissed = $this->preparePushMissedContacts(Arr::get($localChanges, 'added', []), $distContacts, $localContacts);
            $requests = $requests->union($requestsMissed);
        }

        $urls = $requests->pluck('request')->toArray();

        $this->sync->client->requestPool($urls, [
            'concurrency' => 25,
            'fulfilled' => function (ResponseInterface $response, $index) use ($requests) {
                Log::info(__CLASS__.' pushContacts: PUT '.$requests[$index]['uri']);
                $etags = $response->getHeader('Etag');
                if (! empty($etags) && $etags[0] !== $requests[$index]['etag']) {
                    Log::warning(__CLASS__.' pushContacts: wrong etag. Expected '.$requests[$index]['etag'].', get '.$etags[0]);
                }
            },
        ])->wait();
    }

    /**
     * Get list of requests to push contacts that have changed.
     *
     * @param  Collection  $changes
     * @param  array|null  $localChanges
     * @return Collection
     */
    private function preparePushChanges(Collection $changes, ?array $localChanges): Collection
    {
        $requestsChanges = $this->preparePushChangedContacts($changes, Arr::get($localChanges, 'modified', []));
        $requestsAdded = $this->preparePushAddedContacts(Arr::get($localChanges, 'added', []));

        return $requestsChanges->union($requestsAdded);
    }

    /**
     * Get list of requests to push new contacts.
     *
     * @param  array  $contacts
     * @return Collection
     */
    private function preparePushAddedContacts(array $contacts): Collection
    {
        // All added contact must be pushed
        return collect($contacts)
          ->map(function ($uri) {
              $card = $this->sync->backend->getCard($this->sync->subscription->addressbook->name, $uri);
              $card['uri'] = $uri;

              return $card;
          })->map(function ($contact): array {
              $contact['request'] = new Request('PUT', $contact['uri'], [], $contact['carddata']);

              return $contact;
          });
    }

    /**
     * Get list of requests to push modified contacts.
     *
     * @param  Collection  $changes
     * @param  array  $contacts
     * @return Collection
     */
    private function preparePushChangedContacts(Collection $changes, array $contacts): Collection
    {
        $refreshIds = $changes->pluck('href');

        // We don't push contact that have just been pulled
        return collect($contacts)
          ->reject(function (string $uri) use ($refreshIds) {
              $uuid = $this->sync->backend->getUuid($uri);

              return $refreshIds->contains($uuid);
          })->map(function (string $uri) {
              $card = $this->sync->backend->getCard($this->sync->subscription->addressbook->name, $uri);
              $card['uri'] = $uri;

              return $card;
          })->map(function (array $contact): array {
              $contact['request'] = new Request('PUT', $contact['uri'], ['If-Match' => $contact['etag']], $contact['carddata']);

              return $contact;
          });
    }

    /**
     * Get list of requests of missed contacts.
     *
     * @param  array  $added
     * @param  Collection  $distContacts
     * @param  Collection  $localContacts
     * @return Collection
     */
    private function preparePushMissedContacts(array $added, Collection $distContacts, Collection $localContacts): Collection
    {
        $distContacts = $distContacts->map(function ($c) {
            return $this->sync->backend->getUuid($c['href']);
        });
        $added = collect($added)->map(function ($c) {
            return $this->sync->backend->getUuid($c);
        });

        return $localContacts
          ->filter(function (Contact $contact) use ($distContacts, $added) {
              return ! $distContacts->contains($contact->uuid)
                && ! $added->contains($contact->uuid);
          })->map(function (Contact $contact): array {
              $data = $this->sync->backend->prepareCard($contact);

              $data['request'] = new Request('PUT', $data['uri'], ['If-Match' => '*'], $data['carddata']);

              return $data;
          })
            ->values();
    }
}
