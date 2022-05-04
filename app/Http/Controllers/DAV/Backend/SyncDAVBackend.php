<?php

namespace App\Http\Controllers\DAV\Backend;

use App\Traits\WithUser;
use Illuminate\Support\Str;
use App\Models\User\SyncToken;

trait SyncDAVBackend
{
    use WithUser;

    /**
     * This method returns a sync-token for this collection.
     *
     * If null is returned from this function, the plugin assumes there's no
     * sync information available.
     *
     * @param  string|null  $collectionId
     * @return SyncToken|null
     */
    public function getCurrentSyncToken($collectionId): ?SyncToken
    {
        $tokens = SyncToken::where([
            'account_id' => $this->user->account_id,
            'user_id' => $this->user->id,
            'name' => $collectionId ?? $this->backendUri(),
        ])
            ->orderBy('created_at')
            ->get();

        return $tokens->count() > 0 ? $tokens->last() : null;
    }

    /**
     * Create or refresh the token if a change happened.
     *
     * @param  string|null  $collectionId
     * @return SyncToken
     */
    public function refreshSyncToken($collectionId): SyncToken
    {
        $token = $this->getCurrentSyncToken($collectionId);

        if (! $token || $token->timestamp < $this->getLastModified($collectionId)) {
            $token = $this->createSyncTokenNow($collectionId);
        }

        return $token;
    }

    /**
     * Get SyncToken by token id.
     *
     * @param  string|null  $collectionId
     * @param  string  $syncToken
     * @return SyncToken|null
     */
    protected function getSyncToken($collectionId, $syncToken)
    {
        /** @var SyncToken|null */
        return SyncToken::where([
            'account_id' => $this->user->account_id,
            'user_id' => $this->user->id,
            'name' => $collectionId ?? $this->backendUri(),
        ])
            ->find($syncToken);
    }

    /**
     * Create a token with now timestamp.
     *
     * @param  string|null  $collectionId
     * @return SyncToken
     */
    private function createSyncTokenNow($collectionId)
    {
        return SyncToken::create([
            'account_id' => $this->user->account_id,
            'user_id' => $this->user->id,
            'name' => $collectionId ?? $this->backendUri(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Returns the last modification date.
     *
     * @param  string|null  $collectionId
     * @return \Carbon\Carbon|null
     */
    public function getLastModified($collectionId)
    {
        return $this->getObjects($collectionId)
                    ->map(function ($object) {
                        return $object->updated_at;
                    })
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
     *
     * @param  string  $calendarId
     * @param  string  $syncToken
     * @return array|null
     */
    public function getChanges($calendarId, $syncToken): ?array
    {
        $token = null;
        $timestamp = null;
        if (! empty($syncToken)) {
            $token = $this->getSyncToken($calendarId, $syncToken);

            if (is_null($token)) {
                // syncToken is not recognized
                return null;
            }

            $timestamp = $token->timestamp;
        }

        $objs = $this->getObjects($calendarId);

        $modified = $objs->filter(function ($obj) use ($timestamp) {
            return ! is_null($timestamp) &&
                   $obj->updated_at > $timestamp &&
                   $obj->created_at < $timestamp;
        });
        $added = $objs->filter(function ($obj) use ($timestamp) {
            return is_null($timestamp) ||
                   $obj->created_at >= $timestamp;
        });
        $deleted = $this->getDeletedObjects($calendarId)
            ->filter(function ($obj) use ($timestamp) {
                $d = $obj->deleted_at;

                return is_null($timestamp) ||
                       $obj->deleted_at >= $timestamp;
            });

        return [
            'syncToken' => $this->refreshSyncToken($calendarId)->id,
            'added' => $added->map(function ($obj) {
                return $this->encodeUri($obj);
            })->values()->toArray(),
            'modified' => $modified->map(function ($obj) {
                $this->refreshObject($obj);

                return $this->encodeUri($obj);
            })->values()->toArray(),
            'deleted' => $deleted->map(function ($obj) {
                return $this->encodeUri($obj);
            })->values()->toArray(),
        ];
    }

    protected function encodeUri($obj): string
    {
        if (empty($obj->uuid)) {
            // refresh model from database
            $obj->refresh();

            if (empty($obj->uuid)) {
                // in case uuid is still not set, do it
                $obj->forceFill([
                    'uuid' => Str::uuid(),
                ])->save();
            }
        }

        return urlencode($obj->uuid.$this->getExtension());
    }

    private function decodeUri($uri): string
    {
        return pathinfo(urldecode($uri), PATHINFO_FILENAME);
    }

    /**
     * Returns the contact uuid for the specific uri.
     *
     * @param  string  $uri
     * @return string
     */
    public function getUuid($uri): string
    {
        return $this->decodeUri($uri);
    }

    /**
     * Returns the contact for the specific uri.
     *
     * @param  string|null  $collectionId
     * @param  string  $uri
     * @return mixed
     */
    public function getObject($collectionId, $uri)
    {
        try {
            return $this->getObjectUuid($collectionId, $this->getUuid($uri));
        } catch (\Exception $e) {
            // Object not found
        }
    }

    /**
     * Returns the object for the specific uuid.
     *
     * @param  string|null  $collectionId
     * @param  string  $uuid
     * @return mixed
     */
    abstract public function getObjectUuid($collectionId, $uuid);

    /**
     * Returns the collection of objects.
     *
     * @param  string|null  $collectionId
     * @return \Illuminate\Support\Collection
     */
    abstract public function getObjects($collectionId);

    /**
     * Returns the collection of objects.
     *
     * @param  string|null  $collectionId
     * @return \Illuminate\Support\Collection
     */
    abstract public function getDeletedObjects($collectionId);

    abstract public function getExtension();

    /**
     * Get the new exported version of the object.
     *
     * @param  mixed  $obj
     * @return string
     */
    abstract protected function refreshObject($obj): string;
}
