<?php

namespace App\Domains\Vault\ManageTags\Api\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Resources\TagResource;
use App\Http\Resources\ContactTagResource;
use App\Models\Label;
use App\Models\Vault;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Knuckles\Scribe\Attributes\{BodyParam, QueryParam, Response, ResponseFromApiResource, ResponseField};

/**
 * @group Tag management
 *
 * @subgroup Tags
 */
class TagController extends ApiController
{
    private const CACHE_TTL = 600; // 10 minutes
    private const CACHE_KEY_PREFIX = 'tags:vault:';

    public function __construct()
    {
        $this->middleware('abilities:read')->only(['index', 'show']);
        $this->middleware('abilities:write')->only(['store', 'update', 'destroy', 'attachTag', 'detachTag']);

        parent::__construct();
    }

    /**
     * List all tags for the authenticated user's account.
     *
     * Returns all tags with usage count per tag, cached for 10 minutes.
     */
    #[QueryParam('limit', 'int', description: 'A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10.', required: false, example: 10)]
    #[ResponseFromApiResource(TagResource::class, Label::class, collection: true)]
    public function index(Request $request)
    {
        $vault = $this->getAuthorizedVault($request);
        
        // Get and cache the tags with usage counts
        $cacheKey = self::CACHE_KEY_PREFIX . $vault->id;
        
        $tags = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($vault) {
            return $vault->labels()
                ->withCount('contacts')
                ->orderBy('name')
                ->get();
        });

        // Paginate the cached collection
        $page = $request->input('page', 1);
        $perPage = $this->getLimitPerPage();
        $paginatedTags = $tags->slice(($page - 1) * $perPage, $perPage)->values();

        return TagResource::collection($paginatedTags);
    }

    /**
     * Get a specific tag.
     *
     * Retrieve a single tag by ID.
     */
    #[ResponseFromApiResource(TagResource::class, Label::class)]
    public function show(Request $request, string $vaultId, string $tagId)
    {
        $vault = $this->getAuthorizedVault($request, $vaultId);
        
        $tag = $vault->labels()->findOrFail($tagId);

        return new TagResource($tag);
    }

    /**
     * Create a new tag.
     *
     * Creates a tag object in the vault.
     */
    #[BodyParam('name', description: 'The name of the tag. Max 255 characters.')]
    #[BodyParam('category', description: 'Optional category for the tag (e.g., Personal, Work, Networking).', required: false)]
    #[BodyParam('color', description: 'Optional color for the tag (hex format). Default: #6B7280.', required: false)]
    #[BodyParam('description', description: 'Optional description of the tag.', required: false)]
    #[ResponseFromApiResource(TagResource::class, Label::class, status: 201)]
    public function store(Request $request, string $vaultId)
    {
        $vault = $this->getAuthorizedVault($request, $vaultId);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:1000',
        ]);

        $tag = $vault->labels()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'category' => $validated['category'] ?? null,
            'color' => $validated['color'] ?? '#6B7280',
            'description' => $validated['description'] ?? null,
        ]);

        // Invalidate cache
        Cache::forget(self::CACHE_KEY_PREFIX . $vault->id);

        return new TagResource($tag);
    }

    /**
     * Update a tag.
     *
     * Update a specific tag's properties.
     */
    #[BodyParam('name', description: 'The name of the tag.', required: false)]
    #[BodyParam('category', description: 'Optional category for the tag.', required: false)]
    #[BodyParam('color', description: 'Optional color for the tag (hex format).', required: false)]
    #[BodyParam('description', description: 'Optional description of the tag.', required: false)]
    #[ResponseFromApiResource(TagResource::class, Label::class)]
    public function update(Request $request, string $vaultId, string $tagId)
    {
        $vault = $this->getAuthorizedVault($request, $vaultId);
        
        $tag = $vault->labels()->findOrFail($tagId);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'nullable|string|max:255',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:1000',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $tag->update($validated);

        // Invalidate cache
        Cache::forget(self::CACHE_KEY_PREFIX . $vault->id);

        return new TagResource($tag);
    }

    /**
     * Delete a tag.
     *
     * Removes a tag from the vault. This will detach the tag from all contacts.
     */
    #[Response(status: 204)]
    public function destroy(Request $request, string $vaultId, string $tagId)
    {
        $vault = $this->getAuthorizedVault($request, $vaultId);
        
        $tag = $vault->labels()->findOrFail($tagId);
        
        // Delete related pivot table entries before deleting the tag
        // This handles the contact_label table (Many-to-Many)
        DB::table('contact_label')->where('label_id', $tag->id)->delete();
        
        // Delete the tag and its polymorphic relationships
        $tag->delete();

        // Invalidate cache
        Cache::forget(self::CACHE_KEY_PREFIX . $vault->id);

        return response()->noContent();
    }

    /**
     * Attach tags to a contact.
     *
     * Attach one or more tags to a specific contact.
     */
    #[BodyParam('tag_ids', description: 'Array of tag IDs to attach to the contact.')]
    #[Response(status: 200)]
    public function attachTag(Request $request, string $vaultId, string $contactId)
    {
        $vault = $this->getAuthorizedVault($request, $vaultId);
        
        $contact = $vault->contacts()->findOrFail($contactId);

        $validated = $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'required|integer|exists:labels,id',
        ]);

        // Verify all tags belong to this vault
        $tagIds = $validated['tag_ids'];
        $vault->labels()->whereIn('id', $tagIds)->get();

        // Sync tags
        $contact->labels()->syncWithoutDetaching($tagIds);

        // Invalidate cache
        Cache::forget(self::CACHE_KEY_PREFIX . $vault->id);

        return response()->json([
            'message' => 'Tags attached successfully',
            'contact_id' => $contact->id,
            'tag_ids' => $tagIds,
        ]);
    }

    /**
     * Detach a tag from a contact.
     *
     * Remove a specific tag from a contact.
     */
    #[Response(status: 204)]
    public function detachTag(Request $request, string $vaultId, string $contactId, string $tagId)
    {
        $vault = $this->getAuthorizedVault($request, $vaultId);
        
        $contact = $vault->contacts()->findOrFail($contactId);
        
        $tag = $vault->labels()->findOrFail($tagId);

        // Detach the tag
        $contact->labels()->detach($tag->id);

        // Invalidate cache
        Cache::forget(self::CACHE_KEY_PREFIX . $vault->id);

        return response()->noContent();
    }

    /**
     * Get authorized vault.
     *
     * Ensures the user has access to the vault.
     */
    private function getAuthorizedVault(Request $request, ?string $vaultId = null): Vault
    {
        $vaultId = $vaultId ?: $request->route('vaultId');
        
        $vault = $request->user()->account->vaults()
            ->findOrFail($vaultId);

        return $vault;
    }
}
