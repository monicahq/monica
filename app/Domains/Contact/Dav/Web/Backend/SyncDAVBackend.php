<?php

namespace App\Domains\Contact\Dav\Web\Backend;

use App\Domains\Contact\Dav\IDavResource;
use App\Models\SyncToken;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait SyncDAVBackend
{
    /**
     * This method returns a sync-token for this collection.
     *
     * If null is returned from this function, the plugin assumes there's no
     * sync information available.
     */
    public function getCurrentSyncToken(?string $collectionId): ?SyncToken
    {
        return SyncToken::where([
            'account_id' => $this->user->account_id,
            'user_id' => $this->user->id,
            'name' => $this->backendId($collectionId),
        ])
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Create or refresh the token if a change happened.
     */
    public function refreshSyncToken(?string $collectionId): SyncToken
    {
        $token = $this->getCurrentSyncToken($collectionId);

        if (! $token || $token->timestamp < $this->getLastModified($collectionId)) {
            $token = $this->createSyncTokenNow($collectionId);
        }

        return $token;
    }

    /**
     * Get SyncToken by token id.
     */
    protected function getSyncToken(?string $collectionId, int $syncTokenId): ?SyncToken
    {
        /** @var SyncToken|null */
        return SyncToken::where([
            'account_id' => $this->user->account_id,
            'user_id' => $this->user->id,
            'name' => $this->backendId($collectionId),
        ])
            ->find($syncTokenId);
    }

    /**
     * Create a token with now timestamp.
     */
    private function createSyncTokenNow(?string $collectionId): SyncToken
    {
        return SyncToken::create([
            'account_id' => $this->user->account_id,
            'user_id' => $this->user->id,
            'name' => $this->backendId($collectionId),
            'timestamp' => now(),
        ]);
    }

    /**
     * Returns the last modification date.
     */
    public function getLastModified(?string $collectionId): ?Carbon
    {
        return $this->getObjects($collectionId)
            ->map(fn (IDavResource $object) => $object->updated_at)
            ->max();
    }

    /**
     * The getChanges method returns all the changes that have happened, since
     * the specified syncToken.
     *
     * This function should return an array, such as the following:
     *
     * [
     *   'syncToken' => 'The current synctoken',
     *   'added'   => [
     *      'new.txt',
     *   ],
     *   'modified'   => [
     *      'modified.txt',
     *   ],
     *   'deleted' => [
     *      'foo.php.bak',
     *      'old.txt'
     *   ]
     * );
     *
     * The returned syncToken property should reflect the *current* syncToken
     * , as reported in the {http://sabredav.org/ns}sync-token
     * property This is * needed here too, to ensure the operation is atomic.
     *
     * If the $syncToken argument is specified as null, this is an initial
     * sync, and all members should be reported.
     *
     * The modified property is an array of nodenames that have changed since
     * the last token.
     *
     * The deleted property is an array with nodenames, that have been deleted
     * from collection.
     *
     * The $syncLevel argument is basically the 'depth' of the report. If it's
     * 1, you only have to report changes that happened only directly in
     * immediate descendants. If it's 2, it should also include changes from
     * the nodes below the child collections. (grandchildren)
     *
     * The $limit argument allows a client to specify how many results should
     * be returned at most. If the limit is not specified, it should be treated
     * as infinite.
     *
     * If the limit (infinite or not) is higher than you're willing to return,
     * you should throw a Sabre\DAV\Exception\TooMuchMatches() exception.
     *
     * If the syncToken is expired (due to data cleanup) or unknown, you must
     * return null.
     *
     * The limit is 'suggestive'. You are free to ignore it.
     */
    public function getChanges(?string $collectionId, ?string $syncToken): ?array
    {
        $token = null;
        $timestamp = null;
        if ($syncToken !== null && $syncToken !== '') {
            $token = $this->getSyncToken($collectionId, (int) $syncToken);

            if ($token === null) {
                // syncToken is not recognized
                return null;
            }

            $timestamp = $token->timestamp;
        }

        $objs = $this->getObjects($collectionId);

        return [
            'syncToken' => $this->refreshSyncToken($collectionId)->id,
            'added' => $this->getAdded($objs, $timestamp),
            'modified' => $this->getModified($objs, $timestamp),
            'deleted' => $this->getDeleted($collectionId, $timestamp),
        ];
    }

    /**
     * Get the added objects.
     */
    private function getAdded(Collection $objs, ?Carbon $timestamp): array
    {
        return $objs->filter(fn (IDavResource $obj): bool => $timestamp === null ||
            $obj->created_at >= $timestamp
        )
            ->map(fn ($obj): string => $this->encodeUri($obj))
            ->values()
            ->toArray();
    }

    /**
     * Get the modified objects.
     */
    private function getModified(Collection $objs, ?Carbon $timestamp): array
    {
        return $objs->filter(fn (IDavResource $obj): bool => $timestamp !== null &&
            $obj->updated_at > $timestamp &&
            $obj->created_at < $timestamp
        )
            ->map(function (IDavResource $obj): string {
                $this->refreshObject($obj);

                return $this->encodeUri($obj);
            })
            ->values()
            ->toArray();
    }

    /**
     * Get the deleted objects.
     */
    private function getDeleted(string $collectionId, ?Carbon $timestamp): array
    {
        return $this->getDeletedObjects($collectionId)
            ->filter(fn (IDavResource $obj): bool => $timestamp === null ||
                $obj->deleted_at >= $timestamp
            )
            ->map(fn (IDavResource $obj): string => $this->encodeUri($obj))
            ->values()
            ->toArray();
    }

    protected function encodeUri(IDavResource $obj): string
    {
        $id = null;
        if (isset($obj->distant_uuid)) {
            $id = Str::of($obj->distant_uuid)->after('urn:uuid:');
        }
        if ($id === null && isset($obj->uuid)) {
            $id = $obj->uuid;
        }
        if ($id === null) {
            $id = $obj->id;
        }

        return urlencode($id.$this->getExtension());
    }

    private function decodeUri(string $uri): string
    {
        return pathinfo(urldecode($uri), PATHINFO_FILENAME);
    }

    /**
     * Returns the contact uuid for the specific uri.
     */
    public function getUuid(string $uri): string
    {
        return $this->decodeUri($uri);
    }

    /**
     * Returns the contact for the specific uri.
     *
     * @return mixed
     */
    public function getObject(?string $collectionId, string $uri)
    {
        try {
            return $this->getObjectUuid($collectionId, $this->getUuid($uri));
        } catch (\Exception $e) {
            // Object not found
        }
    }

    /**
     * Returns the object for the specific uuid.
     */
    abstract public function getObjectUuid(?string $collectionId, string $uuid): mixed;

    /**
     * Returns the collection of objects.
     *
     * @return \Illuminate\Support\Collection<array-key,IDavResource>
     */
    abstract public function getObjects(?string $collectionId): Collection;

    /**
     * Returns the collection of objects.
     *
     * @return \Illuminate\Support\Collection<array-key,IDavResource>
     */
    abstract public function getDeletedObjects(?string $collectionId): Collection;

    /**
     * Extension for Calendar objects.
     */
    abstract public function getExtension(): string;

    /**
     * Get the new exported version of the object.
     */
    abstract protected function refreshObject(IDavResource $obj): string;
}
