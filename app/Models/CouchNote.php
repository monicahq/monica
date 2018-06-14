<?php

namespace App\Models;

use Parsedown;
use Jenssegers\Model\Model;
use App\Helpers\CouchDbHelper;

class CouchNote extends Model
{
    protected $hidden = [];
    protected $guarded = [];

    /**
     * Return the markdown parsed body.
     *
     * @return string
     */
    public function getParsedBodyAttribute()
    {
        return (new Parsedown())->text($this->body);
    }

    /**
     * Save the doc in the database.
     *
     * @return CouchNote
     */
    public function save($accountId = null)
    {
        if (! $accountId) {
            $accountId = auth()->user()->account->id;
        }
        $client = CouchDbHelper::getAccountDatabase($accountId);

        return $client->storeDoc($this);
    }

    /**
     * Remove the doc from the database.
     *
     * @return void
     */
    public function delete($accountId = null)
    {
        if (! $accountId) {
            $accountId = auth()->user()->account->id;
        }
        $client = CouchDbHelper::getAccountDatabase($accountId);

        return $client->deleteDoc($this);
    }

    /**
     * Get a note from his _id.
     *
     * @return CouchNote
     */
    public static function getOneById($accountId, $noteId)
    {
        $client = CouchDbHelper::getAccountDatabase($accountId);
        try {
            $doc = $client->getDoc($noteId);
        } catch (Exception $e) {
            if ($e->getCode() == 404) {
                return;
            }
        }

        return new self((array) $doc);
    }

    /**
     * Create a note.
     *
     * @return CouchNote
     */
    public static function create($accountId, $note)
    {
        $client = CouchDbHelper::getAccountDatabase($accountId);

        $note->type = 'note';
        if (! $note->created_at) {
            $note->created_at = now()->toDateTimeString();
        }
        if (! $note->updated_at) {
            $note->updated_at = now()->toDateTimeString();
        }
        $response = $client->storeDoc($note);

        $note->_id = $response->id;
        $note->_rev = $response->rev;

        return $note;
    }
}
