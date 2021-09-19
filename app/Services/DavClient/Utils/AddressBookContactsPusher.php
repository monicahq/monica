<?php

namespace App\Services\DavClient\Utils;

use GuzzleHttp\Psr7\Request;
use App\Models\Contact\Contact;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\PromiseInterface;
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
     * @return PromiseInterface
     */
    public function execute(SyncDto $sync, Collection $changes, ?array $localChanges, ?Collection $distContacts = null, ?Collection $localContacts = null): PromiseInterface
    {
        $this->sync = $sync;

        $requests = $this->preparePushChanges($changes, $localChanges);

        if ($distContacts !== null && $localContacts !== null) {
            $requestsMissed = $this->preparePushMissedContacts(Arr::get($localChanges, 'added', []), $distContacts, $localContacts);
            $requests = $requests->union($requestsMissed);
        }

        $urls = $requests->pluck('request')->toArray();

        return $this->sync->client->requestPool($urls, [
            'concurrency' => 25,
            'fulfilled' => function (ResponseInterface $response, $index) use ($requests) {
                Log::info(__CLASS__.' pushContacts: PUT '.$requests[$index]['uri']);

                $etags = $response->getHeader('Etag');
                if (! empty($etags) && $etags[0] !== $requests[$index]['etag']) {
                    Log::warning(__CLASS__.' pushContacts: wrong etag when updating contact. Expected '.$requests[$index]['etag'].', get '.$etags[0]);
                }
            },
        ]);
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
          ->map(function (string $uri): array {
              $card = $this->sync->backend->getCard($this->sync->subscription->addressbook->name, $uri);

              if ($card === false) {
                  return [];
              }

              return [
                  'uri' => $uri,
                  'request' => new Request('PUT', $uri, [], $card['carddata']),
                  'etag' => $card['etag'],
              ];
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
          ->reject(function (string $uri) use ($refreshIds): bool {
              $uuid = $this->sync->backend->getUuid($uri);

              return $refreshIds->contains($uuid);
          })->map(function (string $uri): array {
              $card = $this->sync->backend->getCard($this->sync->subscription->addressbook->name, $uri);

              if ($card === false) {
                  return [];
              }

              return [
                  'uri' => $uri,
                  'request' => new Request('PUT', $uri, ['If-Match' => $card['etag']], $card['carddata']),
                  'etag' => $card['etag'],
              ];
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
              $card = $this->sync->backend->prepareCard($contact);

              $card['request'] = new Request('PUT', $card['uri'], ['If-Match' => '*'], $card['carddata']);

              return $card;
          })
            ->values();
    }
}
