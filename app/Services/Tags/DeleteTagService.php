<?php

namespace App\Services\Tags;

use App\Models\Tag;
use App\Services\Tags\Concerns\BelongsToVault;

class DeleteTagService
{
    use BelongsToVault;

    public function __construct(
        private TagCacheService $cache,
    ) {}

    public function execute(Tag $tag): void
    {
        $this->ensureTagBelongsToVault($tag);

        $tag->contacts()->detach();
        $tag->delete();

        $this->cache->forget();
    }
}
