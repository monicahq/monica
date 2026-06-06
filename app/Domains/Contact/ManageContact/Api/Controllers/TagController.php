<?php

namespace App\Domains\Contact\ManageContact\Api\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * @group Tag management
 *
 * @subgroup Tags
 */
class TagController extends ApiController
{
    public function __construct()
    {
        $this->middleware('abilities:read')->only(['index', 'show']);
        $this->middleware('abilities:write')->only(['store', 'update', 'destroy']);

        parent::__construct();
    }

    // -------------------------------------------------------------------------
    // GET /api/vaults/{vault}/tags
    // -------------------------------------------------------------------------

    /**
     * List all tags for a vault with usage count (cached for 10 minutes).
     *
     * Returns tags with the number of contacts each tag is attached to.
     * The response is cached in Redis with a 10-minute TTL and invalidated
     * whenever a tag is created, updated, deleted, or attached/detached.
     *
     * Cache key format: `tags:vault:{vault_id}`
     */
    public function index(Request $request, string $vaultId)
    {
        $vault = $request->user()->account->vaults()->findOrFail($vaultId);

        // Cache key for this vault's tags
        $cacheKey = "tags:vault:{$vaultId}";

        // Try to get from cache
        $tags = Cache::remember($cacheKey, 600, function () use ($vault) {
            // Fetch all tags for the vault with contact count
            return $vault->tags()
                ->withCount('contacts')
                ->orderBy('name', 'asc')
                ->get()
                ->map(function ($tag) {
                    $tag->usage_count = $tag->contacts_count;
                    return $tag;
                });
        });

        return TagResource::collection($tags);
    }

    // -------------------------------------------------------------------------
    // POST /api/vaults/{vault}/tags
    // -------------------------------------------------------------------------

    /**
     * Create a new tag.
     *
     * Request body:
     * {
     *   "name": "Colleague",
     *   "tag_category": "Work",
     *   "color": "#FF5733"
     * }
     */
    public function store(Request $request, string $vaultId)
    {
        $vault = $request->user()->account->vaults()->findOrFail($vaultId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tag_category' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
        ]);

        // Create the tag
        $tag = $vault->tags()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'tag_category' => $validated['tag_category'] ?? null,
            'color' => $validated['color'] ?? null,
        ]);

        // Invalidate the tag list cache
        $this->invalidateTagCache($vaultId);

        return new TagResource($tag);
    }

    // -------------------------------------------------------------------------
    // PUT /api/vaults/{vault}/tags/{tag}
    // -------------------------------------------------------------------------

    /**
     * Update a tag.
     *
     * Request body (any combination):
     * {
     *   "name": "New Name",
     *   "tag_category": "New Category",
     *   "color": "#AABBCC"
     * }
     */
    public function update(Request $request, string $vaultId, int $tagId)
    {
        $vault = $request->user()->account->vaults()->findOrFail($vaultId);
        $tag = $vault->tags()->findOrFail($tagId);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'tag_category' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[A-Fa-f0-9]{6}$/'],
        ]);

        // Update tag attributes
        if (isset($validated['name'])) {
            $tag->name = $validated['name'];
            $tag->slug = Str::slug($validated['name']);
        }
        if (isset($validated['tag_category'])) {
            $tag->tag_category = $validated['tag_category'];
        }
        if (isset($validated['color'])) {
            $tag->color = $validated['color'];
        }

        $tag->save();

        // Invalidate the tag list cache
        $this->invalidateTagCache($vaultId);

        // Add usage count to response
        $tag->usage_count = $tag->contacts()->count();

        return new TagResource($tag);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/vaults/{vault}/tags/{tag}
    // -------------------------------------------------------------------------

    /**
     * Delete a tag.
     *
     * By default, this removes the tag from all contacts.
     * Optional request parameter:
     * - reassign_to: (optional) Tag ID to reassign all contacts to another tag
     *
     * Example request body:
     * {
     *   "reassign_to": 5
     * }
     */
    public function destroy(Request $request, string $vaultId, int $tagId)
    {
        $vault = $request->user()->account->vaults()->findOrFail($vaultId);
        $tag = $vault->tags()->findOrFail($tagId);

        $validated = $request->validate([
            'reassign_to' => ['nullable', 'integer', 'exists:tags,id'],
        ]);

        // If reassigning, move all contacts from this tag to the target tag
        if (isset($validated['reassign_to']) && $validated['reassign_to'] != $tagId) {
            $targetTag = $vault->tags()->findOrFail($validated['reassign_to']);

            // Get all contacts with this tag
            $contactIds = $tag->contacts()->pluck('contacts.id')->toArray();

            // Sync them to the target tag (keeping existing tags)
            foreach ($contactIds as $contactId) {
                $targetTag->contacts()->attach($contactId);
            }
        }

        // Delete the tag (will detach from all contacts due to cascade)
        $tag->delete();

        // Invalidate the tag list cache
        $this->invalidateTagCache($vaultId);

        return $this->respondObjectDeleted((string) $tagId);
    }

    // -------------------------------------------------------------------------
    // Helper Methods
    // -------------------------------------------------------------------------

    /**
     * Invalidate the tag list cache for a vault.
     *
     * Cache key format: `tags:vault:{vault_id}`
     */
    private function invalidateTagCache(string $vaultId): void
    {
        Cache::forget("tags:vault:{$vaultId}");
    }
}
