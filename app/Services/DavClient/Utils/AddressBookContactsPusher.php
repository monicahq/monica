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
use App\Services\DavClient\Utils\Model\ContactDto;
use App\Services\DavClient\Utils\Model\ContactPushDto;
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
     * @param  Collection<array-key, ContactDto>  $changes
     * @param  array<array-key, string>|null  $localChanges
     * @param  Collection<array-key, ContactDto>|null  $distContacts
     * @param  Collection<array-key, Contact>|null  $localContacts
     * @return PromiseInterface
     */
    public function execute(SyncDto $sync, Collection $changes, ?array $localChanges, ?Collection $distContacts = null, ?Collection $localContacts = null): PromiseInterface
    {
        $this->sync = $sync;

        $commands = $this->preparePushChanges($changes, $localChanges);

        if ($distContacts !== null && $localContacts !== null) {
            $missed = $this->preparePushMissedContacts(Arr::get($localChanges, 'added', []), $distContacts, $localContacts);
            $commands = $commands->union($missed);
        }

        $commands = $commands->filter(function ($command) {
            return $command !== null;
        });

        $requests = $commands->pluck('request')->toArray();

        return $this->sync->client->requestPool($requests, [
            'concurrency' => 25,
            'fulfilled' => function (ResponseInterface $response, int $index) use ($commands) {
                /** @var ContactPushDto $command */
                $command = $commands[$index];

                Log::info(__CLASS__.' pushContacts: PUT '.$command->uri);

                $etags = $response->getHeader('Etag');
                if (! empty($etags) && $etags[0] !== $command->etag) {
                    Log::warning(__CLASS__.' pushContacts: wrong etag when updating contact. Expected '.$command->etag.', get '.$etags[0]);
                }
            },
        ]);
    }

    /**
     * Get list of requests to push contacts that have changed.
     *
     * @param  Collection<array-key, ContactDto>  $changes
     * @param  array<array-key, string>|null  $localChanges
     * @return Collection<mixed, ?ContactPushDto>
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
     * @return Collection<mixed, ?ContactPushDto>
     */
    private function preparePushAddedContacts(array $contacts): Collection
    {
        // All added contact must be pushed
        return collect($contacts)
          ->map(function (string $uri): ?ContactPushDto {
              $card = $this->sync->backend->getCard($this->sync->subscription->addressbook->name, $uri);

              if ($card === false) {
                  return null;
              }

              return new ContactPushDto($uri, $card['etag'], new Request('PUT', $uri, [], $card['carddata']));
          });
    }

    /**
     * Get list of requests to push modified contacts.
     *
     * @param  Collection<array-key, ContactDto>  $changes
     * @param  array  $contacts
     * @return Collection<mixed, ?ContactPushDto>
     */
    private function preparePushChangedContacts(Collection $changes, array $contacts): Collection
    {
        $refreshIds = $changes->map(function (ContactDto $contact) {
            return $this->sync->backend->getUuid($contact->uri);
        });

        // We don't push contact that have just been pulled
        return collect($contacts)
          ->reject(function (string $uri) use ($refreshIds): bool {
              $uuid = $this->sync->backend->getUuid($uri);

              return $refreshIds->contains($uuid);
          })->map(function (string $uri): ?ContactPushDto {
              $card = $this->sync->backend->getCard($this->sync->subscription->addressbook->name, $uri);

              if ($card === false) {
                  return null;
              }

              return new ContactPushDto($uri, $card['etag'], new Request('PUT', $uri, ['If-Match' => $card['etag']], $card['carddata']));
          });
    }

    /**
     * Get list of requests of missed contacts.
     *
     * @param  array<array-key, string>  $added
     * @param  Collection<array-key, ContactDto>  $distContacts
     * @param  Collection<array-key, Contact>  $localContacts
     * @return Collection<array-key, ContactPushDto>
     */
    private function preparePushMissedContacts(array $added, Collection $distContacts, Collection $localContacts): Collection
    {
        /** @var Collection<array-key, string> $distUuids */
        $distUuids = $distContacts->map(function (ContactDto $contact) {
            return $this->sync->backend->getUuid($contact->uri);
        });
        /** @var Collection<array-key, string> $added */
        $addedUuids = collect($added)->map(function ($uri) {
            return $this->sync->backend->getUuid($uri);
        });

        return collect($localContacts)
          ->filter(function (Contact $contact) use ($distUuids, $addedUuids) {
              return ! $distUuids->contains($contact->uuid)
                && ! $addedUuids->contains($contact->uuid);
          })->map(function (Contact $contact): ContactPushDto {
              $card = $this->sync->backend->prepareCard($contact);

              return new ContactPushDto($card['uri'], $card['etag'], new Request('PUT', $card['uri'], ['If-Match' => '*'], $card['carddata']));
          })
            ->values();
    }
}
