<?php

namespace App\Domains\Contact\ManageContact\Api\Controllers;

use App\Domains\Contact\ManageContact\Api\Queries\ContactQuery;
use App\Domains\Contact\ManageContact\Services\GetContactStatistics;
use App\Domains\Contact\ManageContact\Services\MarkContactAsFavorite;
use App\Domains\Contact\ManageContact\Services\RemoveContactFromFavorites;
use App\Domains\Contact\ManageContact\Services\ToggleFavoriteContact;
use App\Domains\Contact\ManageContact\Services\UpdateContactPersonalNote;
use App\Http\Controllers\ApiController;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\{BodyParam,QueryParam,Response,ResponseFromApiResource};

/**
 * @group Contact management
 *
 * @subgroup Contacts
 */
class ContactController extends ApiController
{
    public function __construct()
    {
        $this->middleware('abilities:read')->only(['index', 'show', 'favorites', 'stats']);
        $this->middleware('abilities:write')->only(['markFavorite', 'removeFavorite', 'toggleFavorite', 'updateNote']);

        parent::__construct();
    }

    /**
     * List all contacts.
     *
     * Get all the contacts in a vault with optional filtering by favorite status and search term.
     */
    #[QueryParam('limit', 'int', description: 'A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10.', required: false, example: 10)]
    #[QueryParam('favorite', 'int', description: 'Filter by favorite status. 1 for favorites only, 0 for non-favorites only.', required: false, example: 1)]
    #[QueryParam('search', 'string', description: 'Search term to filter contacts by name (first, last, middle, nickname, or maiden name).', required: false, example: 'john')]
    #[ResponseFromApiResource(ContactResource::class, Contact::class, collection: true)]
    public function index(Request $request, string $vaultId)
    {
        $query = $request->user()->account->vaults()
            ->findOrFail($vaultId)
            ->contacts()
            ->where('listed', true);

        // Apply filters using ContactQuery
        $query = ContactQuery::apply($query, $request);

        $contacts = $query->paginate($this->getLimitPerPage());

        return ContactResource::collection($contacts);
    }

    /**
     * Retrieve a contact.
     *
     * Get a specific contact object with all details including favorite status and personal note.
     */
    #[ResponseFromApiResource(ContactResource::class, Contact::class)]
    public function show(Request $request, string $vaultId, string $contactId)
    {
        $contact = $request->user()->account->vaults()
            ->findOrFail($vaultId)
            ->contacts()
            ->findOrFail($contactId);

        return new ContactResource($contact);
    }

    /**
     * List favorite contacts.
     *
     * Get all favorite contacts in a vault.
     */
    #[QueryParam('limit', 'int', description: 'A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10.', required: false, example: 10)]
    #[ResponseFromApiResource(ContactResource::class, Contact::class, collection: true)]
    public function favorites(Request $request, string $vaultId)
    {
        $contacts = $request->user()->account->vaults()
            ->findOrFail($vaultId)
            ->contacts()
            ->where('is_favorite', true)
            ->where('listed', true)
            ->paginate($this->getLimitPerPage());

        return ContactResource::collection($contacts);
    }

    /**
     * Mark contact as favorite.
     *
     * Marks a contact as favorite.
     */
    #[ResponseFromApiResource(ContactResource::class, Contact::class)]
    public function markFavorite(Request $request, string $vaultId, string $contactId)
    {
        $data = [
            'account_id' => $request->user()->account_id,
            'author_id' => $request->user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
        ];

        $contact = (new MarkContactAsFavorite)->execute($data);

        return new ContactResource($contact);
    }

    /**
     * Remove contact from favorites.
     *
     * Removes a contact from favorites.
     */
    #[Response(['deleted' => true, 'id' => 1])]
    public function removeFavorite(Request $request, string $vaultId, string $contactId)
    {
        $data = [
            'account_id' => $request->user()->account_id,
            'author_id' => $request->user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
        ];

        $contact = (new RemoveContactFromFavorites)->execute($data);

        return new ContactResource($contact);
    }

    /**
     * Toggle favorite status.
     *
     * Toggles the favorite status of a contact.
     */
    #[ResponseFromApiResource(ContactResource::class, Contact::class)]
    public function toggleFavorite(Request $request, string $vaultId, string $contactId)
    {
        $data = [
            'account_id' => $request->user()->account_id,
            'author_id' => $request->user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
        ];

        $contact = (new ToggleFavoriteContact)->execute($data);
        
        // Refresh to get the updated is_favorite value
        $contact->refresh();

        return new ContactResource($contact);
    }

    /**
     * Update personal note.
     *
     * Updates the personal note for a contact.
     */
    #[BodyParam('personal_note', description: 'The personal note content. Max 65535 characters.', required: false)]
    #[ResponseFromApiResource(ContactResource::class, Contact::class)]
    public function updateNote(Request $request, string $vaultId, string $contactId)
    {
        $data = [
            'account_id' => $request->user()->account_id,
            'author_id' => $request->user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'personal_note' => $request->input('personal_note'),
        ];

        $contact = (new UpdateContactPersonalNote)->execute($data);

        return new ContactResource($contact);
    }

    /**
     * Get contact statistics.
     *
     * Returns summary statistics for contacts in the vault.
     */
    #[Response([
        'total_contacts' => 125,
        'favorite_contacts' => 18,
        'contacts_with_notes' => 42,
    ])]
    public function stats(Request $request, string $vaultId)
    {
        $data = [
            'account_id' => $request->user()->account_id,
            'author_id' => $request->user()->id,
            'vault_id' => $vaultId,
        ];

        $statistics = (new GetContactStatistics)->execute($data);

        return response()->json($statistics);
    }
}
