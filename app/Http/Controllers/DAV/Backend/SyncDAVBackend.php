<?php

namespace App\Http\Controllers\DAV\Backend;

use Illuminate\Support\Str;
use App\Models\User\SyncToken;
use Illuminate\Support\Facades\Auth;

trait SyncDAVBackend
{
    /**
     * This method returns a sync-token for this collection.
     *
     * If null is returned from this function, the plugin assumes there's no
     * sync information available.
     *
     * @return SyncToken|null
     */
    protected function getCurrentSyncToken(): ?SyncToken
    {
        $tokens = SyncToken::where([
            'account_id' => Auth::user()->account_id,
            'user_id' => Auth::user()->id,
            'name' => $this->backendUri(),
        ])
            ->orderBy('created_at')
            ->get();

        return $tokens->count() > 0 ? $tokens->last() : null;
    }

    /**
     * Create or refresh the token if a change happened.
     *
     * @return SyncToken
     */
    public function refreshSyncToken(): SyncToken
    {
        $token = $this->getCurrentSyncToken();

        if (! $token || $token->timestamp < $this->getLastModified()) {
            $token = $this->createSyncTokenNow();
        }

        return $token;
    }

    /**
     * Get SyncToken by token id.
     *
     * @return SyncToken|null
     */
    protected function getSyncToken($syncToken)
    {
        return SyncToken::where([
            'account_id' => Auth::user()->account_id,
            'user_id' => Auth::user()->id,
            'name' => $this->backendUri(),
        ])
            ->find($syncToken);
    }

    /**
     * Create a token with now timestamp.
     *
     * @return SyncToken
     */
    private function createSyncTokenNow()
    {
        return SyncToken::create([
            'account_id' => Auth::user()->account_id,
            'user_id' => Auth::user()->id,
            'name' => $this->backendUri(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Returns the last modification date.
     *
     * @return \Carbon\Carbon|null
     */
    public function getLastModified()
    {
        return $this->getObjects()
                    ->max('updated_at');
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
     * @param string $syncToken
     * @return array|null
     */
    public function getChanges($syncToken): ?array
    {
        $token = null;
        $timestamp = null;
        if (! empty($syncToken)) {
            $token = $this->getSyncToken($syncToken);

            if (is_null($token)) {
                // syncToken is not recognized
                return null;
            }

            $timestamp = $token->timestamp;
        }

        $objs = $this->getObjects();

        $modified = $objs->filter(function ($obj) use ($timestamp) {
            return ! is_null($timestamp) &&
                   $obj->updated_at > $timestamp &&
                   $obj->created_at < $timestamp;
        });
        $added = $objs->filter(function ($obj) use ($timestamp) {
            return is_null($timestamp) ||
                   $obj->created_at >= $timestamp;
        });

        return [
            'syncToken' => $this->refreshSyncToken()->id,
            'added' => $added->map(function ($obj) {
                return $this->encodeUri($obj);
            })->values()->toArray(),
            'modified' => $modified->map(function ($obj) {
                return $this->encodeUri($obj);
            })->values()->toArray(),
            'deleted' => [],
        ];
    }

    protected function encodeUri($obj)
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

    private function decodeUri($uri)
    {
        return pathinfo(urldecode($uri), PATHINFO_FILENAME);
    }

    /**
     * Returns the contact for the specific uri.
     *
     * @param string  $uri
     * @return mixed
     */
    public function getObject($uri)
    {
        try {
            return $this->getObjectUuid($this->decodeUri($uri));
        } catch (\Exception $e) {
            // Object not found
        }
    }

    /**
     * Returns the object for the specific uuid.
     *
     * @param string  $uuid
     * @return mixed
     */
    abstract public function getObjectUuid($uuid);

    /**
     * Returns the collection of objects.
     *
     * @return \Illuminate\Support\Collection
     */
    abstract public function getObjects();

    abstract public function getExtension();
}
