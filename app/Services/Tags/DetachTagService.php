<?php

namespace App\Services\Tags;

use App\Models\Contact;
use App\Models\Tag;
use App\Services\Tags\Concerns\BelongsToVault;

class DetachTagService
{
    use BelongsToVault;

    public function __construct(
        private TagCacheService $cache,
    ) {}

    public function execute(Contact $contact, int $tagId): void
    {
        $this->ensureContactBelongsToVault($contact);

        $tag = Tag::query()
            ->where('vault_id', $this->vaultId())
            ->findOrFail($tagId);

        $contact->tags()->detach($tag->id);

        $this->cache->forget();
    }
}
