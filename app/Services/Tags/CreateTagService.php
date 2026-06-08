<?php

namespace App\Services\Tags;

use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateTagService
{
    public function __construct(
        private TagCacheService $cache,
    ) {}

    public function execute(array $data): Tag
    {
        $tag = Tag::create([
            'vault_id' => Auth::user()->vault_id,
            'name' => $data['name'],
            'slug' => Str::slug($data['name'], '-', language: currentLang()),
            'tag_category' => $data['tag_category'] ?? null,
            'color' => $data['color'] ?? null,
        ]);

        $this->cache->forget();

        return $tag;
    }
}
