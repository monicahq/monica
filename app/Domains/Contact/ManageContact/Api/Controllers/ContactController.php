<?php

namespace App\Domains\Contact\ManageContact\Api\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\{QueryParam, ResponseFromApiResource};

/**
 * @group Contact management
 *
 * @subgroup Contacts
 */
class ContactController extends ApiController
{
    public function __construct()
    {
        $this->middleware('abilities:read');
        parent::__construct();
    }

    /**
     * List all contacts.
     *
     * Get all contacts in a vault. Supports filtering by tags using AND logic
     * (contact must have ALL specified tags).
     */
    #[QueryParam('vault_id', 'string', description: 'The vault ID to filter contacts.', required: true)]
    #[QueryParam('tags[]', 'array', description: 'Array of tag IDs to filter contacts (AND logic).', required: false)]
    #[QueryParam('sort', 'string', description: 'Sort field (name, created_at, etc.).', required: false)]
    #[QueryParam('limit', 'int', description: 'A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10.', required: false, example: 10)]
    #[ResponseFromApiResource(ContactResource::class, Contact::class, collection: true)]
    public function index(Request $request)
    {
        $vault = $request->user()->account->vaults()
            ->findOrFail($request->input('vault_id'));

        $query = $vault->contacts()
            ->with(['labels'])
            ->where('listed', true);

        // Handle tag filtering with AND logic using a single SQL statement
        if ($request->has('tags') && is_array($request->input('tags'))) {
            $tagIds = array_filter((array)$request->input('tags'), 'is_numeric');
            
            if (!empty($tagIds)) {
                // Use JOIN with GROUP BY and HAVING to find contacts with ALL specified tags
                // This is a single SQL query that ensures each contact has all requested tags
                $requiredTagCount = count($tagIds);
                
                $query->whereHas('labels', function ($q) use ($tagIds, $requiredTagCount) {
                    $q->whereIn('labels.id', $tagIds)
                        ->groupBy('contact_id')
                        ->havingRaw("COUNT(DISTINCT labels.id) = ?", [$requiredTagCount]);
                });
            }
        }

        // Handle sorting
        $sortBy = $request->input('sort', 'first_name');
        $sortOrder = $request->input('sort_order', 'asc');
        if (in_array($sortBy, ['first_name', 'last_name', 'created_at', 'updated_at'])) {
            $query->orderBy($sortBy, in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'asc');
        }

        $contacts = $query->paginate($this->getLimitPerPage());

        return ContactResource::collection($contacts);
    }
}
