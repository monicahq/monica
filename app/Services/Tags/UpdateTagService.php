<?php

namespace App\Services\Tags;

use App\Models\Tag;
use App\Services\Tags\Concerns\BelongsToVault;
use Illuminate\Support\Str;

class UpdateTagService
{
    use BelongsToVault;

    public function __construct(
        private TagCacheService $cache,
    ) {}

    public function execute(Tag $tag, array $data): Tag
    {
        $this->ensureTagBelongsToVault($tag);

        $updates = [];

        if (array_key_exists('name', $data)) {
            $updates['name'] = $data['name'];
            $updates['slug'] = Str::slug($data['name'], '-', language: currentLang());
        }

        if (array_key_exists('tag_category', $data)) {
            $updates['tag_category'] = $data['tag_category'];
        }

        if (array_key_exists('color', $data)) {
            $updates['color'] = $data['color'];
        }

        $tag->update($updates);

        $this->cache->forget();

        return $tag->fresh();
    }
}
