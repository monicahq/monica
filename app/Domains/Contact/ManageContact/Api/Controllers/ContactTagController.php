<?php

namespace App\Domains\Contact\ManageContact\Api\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Resources\TagResource;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @group Tag management
 *
 * @subgroup Contact Tags
 */
class ContactTagController extends ApiController
{
    public function __construct()
    {
        $this->middleware('abilities:write');

        parent::__construct();
    }

    // -------------------------------------------------------------------------
    // POST /api/vaults/{vault}/contacts/{contact}/tags
    // -------------------------------------------------------------------------

    /**
     * Attach tags to a contact.
     *
     * Accepts an array of tag IDs and attaches all of them to the contact.
     * Existing tags on the contact are preserved (syncWithoutDetaching).
     * Invalidates the tag usage-count cache for the vault.
     *
     * Request body: { "tag_ids": [1, 2, 3] }
     */
    public function store(Request $request, string $vaultId, string $contactId)
    {
        $vault = $request->user()->account->vaults()->findOrFail($vaultId);
        $contact = $vault->contacts()->findOrFail($contactId);

        $validated = $request->validate([
            'tag_ids' => ['required', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ]);

        // Verify all tags belong to the vault
        $tagIds = $validated['tag_ids'];
        $validTags = $vault->tags()->whereIn('id', $tagIds)->pluck('id')->toArray();

        if (count($validTags) !== count($tagIds)) {
            return $this->setHTTPStatusCode(422)
                ->setErrorCode(41)
                ->respondWithError('Some tag IDs do not belong to this vault');
        }

        // Attach the tags (preserve existing tags)
        $contact->tags()->syncWithoutDetaching($tagIds);

        // Invalidate the tag usage-count cache
        Cache::forget("tags:vault:{$vaultId}");

        // Return the attached tags
        $tags = $contact->tags()->get();

        return TagResource::collection($tags);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/vaults/{vault}/contacts/{contact}/tags/{tag}
    // -------------------------------------------------------------------------

    /**
     * Detach a tag from a contact.
     *
     * Removes the specified tag from the contact.
     * Invalidates the tag usage-count cache for the vault.
     */
    public function destroy(Request $request, string $vaultId, string $contactId, int $tagId)
    {
        $vault = $request->user()->account->vaults()->findOrFail($vaultId);
        $contact = $vault->contacts()->findOrFail($contactId);
        $tag = $vault->tags()->findOrFail($tagId);

        // Detach the tag
        $contact->tags()->detach($tagId);

        // Invalidate the tag usage-count cache
        Cache::forget("tags:vault:{$vaultId}");

        return $this->respondObjectDeleted((string) $tagId);
    }
}

