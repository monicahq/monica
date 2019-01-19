<?php

namespace App\Models\DAV\Backends;

use Sabre\DAV;
use App\Models\User\SyncToken;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Sabre\DAV\Server as SabreServer;
use App\Models\Instance\SpecialDate;
use Sabre\CalDAV\Backend\SyncSupport;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\CalDAV\Backend\AbstractBackend;
use App\Services\VCalendar\ExportVCalendar;

trait AbstractDAVBackend
{
    /**
     * This method returns a sync-token for this collection.
     *
     * If null is returned from this function, the plugin assumes there's no
     * sync information available.
     *
     * @return SyncToken
     */
    protected function getSyncToken()
    {
        $tokens = SyncToken::where([
            ['account_id', Auth::user()->account_id],
            ['user_id', Auth::user()->id],
        ])
            ->orderBy('created_at')
            ->get();

        if ($tokens->count() <= 0) {
            $token = $this->createSyncToken();
        } else {
            $token = $tokens->last();

            if ($token->timestamp < $this->getLastModified()) {
                $token = $this->createSyncToken();
            }
        }

        return $token;
    }

    /**
     * Create a token.
     *
     * @return SyncToken
     */
    private function createSyncToken()
    {
        $max = $this->getLastModified();

        return SyncToken::create([
            'account_id' => Auth::user()->account_id,
            'user_id' => Auth::user()->id,
            'timestamp' => $max,
        ]);
    }

    /**
     * Returns the last modification date.
     *
     * @return \Carbon\Carbon
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
     * @param string $id
     * @param string $syncToken
     * @param int $syncLevel
     * @param int $limit
     * @return array
     */
    function getChanges($id, $syncToken, $syncLevel, $limit = null)
    {
        $token = null;
        $timestamp = null;
        if (! empty($syncToken)) {
            $token = SyncToken::where([
                'account_id' => Auth::user()->account_id,
                'user_id' => Auth::user()->id,
            ])->find($syncToken);

            if (is_null($token)) {
                // syncToken is not recognized
                return;
            }

            $timestamp = $token->timestamp;
            $token = $this->getSyncToken();
        } else {
            $token = $this->createSyncToken();
            $timestamp = null;
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
            'syncToken' => $token->id,
            'added' => $added->map(function ($obj) {
                return $this->encodeUri($obj);
            })->toArray(),
            'modified' => $modified->map(function ($obj) {
                return $this->encodeUri($obj);
            })->toArray(),
            'deleted' => [],
        ];        
    }

    private function encodeUri($date)
    {
        return urlencode($date->uuid.$this->getExtension());
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
    private function getObject($uri)
    {
        return $this->getObjectUuid($this->decodeUri($uri));
    }

    /**
     * Returns the object for the specific uuid.
     *
     * @param string  $uri
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
