<?php

namespace App\Domains\Contact\ManageContact\Api\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Request;

/**
 * @group Contact management
 *
 * @subgroup Contacts
 */
class ContactController extends ApiController
{
    public function __construct()
    {
        $this->middleware('abilities:read')->only(['index']);
        $this->middleware('abilities:write')->only(['store', 'update', 'destroy']);

        parent::__construct();
    }

    // -------------------------------------------------------------------------
    // GET /api/vaults/{vault}/contacts
    // -------------------------------------------------------------------------

    /**
     * List contacts (with optional tag filtering).
     *
     * Filters contacts by tags using AND logic: if ?tags[]=1&tags[]=2 is
     * passed, only contacts that have BOTH tag 1 AND tag 2 are returned.
     *
     * The filtering is implemented as a single SQL statement using INNER JOIN
     * with GROUP BY + HAVING — no PHP loops.
     *
     * Supported query params:
     *   - tags[]     array of tag IDs (AND filter)
     *   - sort       "name" | "created_at" (default: first_name asc)
     *   - limit      1–100 (default: 10)
     */
    public function index(Request $request, string $vaultId)
    {
        $vault = $request->user()->account->vaults()->findOrFail($vaultId);

        $tagIds = $request->input('tags', []);

        // Cast to integers and remove blanks
        $tagIds = array_filter(array_map('intval', (array) $tagIds));

        $query = $vault->contacts()->with('tags')->select('contacts.*');

        // ---------------------------------------------------------------
        // Tag filtering — INNER JOIN + GROUP BY + HAVING
        //
        // Single SQL statement with AND logic:
        //
        // SELECT contacts.*
        // FROM contacts
        // INNER JOIN contact_tag ON contact_tag.contact_id = contacts.id
        // WHERE contact_tag.tag_id IN (1, 2)      -- requested tag IDs
        //   AND contacts.vault_id = ?
        // GROUP BY contacts.id
        // HAVING COUNT(DISTINCT contact_tag.tag_id) = 2  -- all requested tags
        //
        // The HAVING clause ensures contacts have ALL requested tags (AND logic).
        // GROUP BY groups results by contact, DISTINCT ensures we count each
        // tag only once per contact.
        // ---------------------------------------------------------------
        if (! empty($tagIds)) {
            $tagCount = count($tagIds);

            $query->join('contact_tag', 'contact_tag.contact_id', '=', 'contacts.id')
                ->whereIn('contact_tag.tag_id', $tagIds)
                ->groupBy('contacts.id')
                ->havingRaw('COUNT(DISTINCT contact_tag.tag_id) = ?', [$tagCount])
        }

        // Sorting
        $sort = $request->input('sort', 'first_name');
        $allowedSorts = ['first_name', 'last_name', 'created_at', 'updated_at'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort);
        } else {
            $query->orderBy('first_name');
        }

        $contacts = $query->paginate($this->getLimitPerPage());

        return ContactResource::collection($contacts);
    }
}
