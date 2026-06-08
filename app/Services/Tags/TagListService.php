<?php

namespace App\Services\Tags;

use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TagListService
{
    public function __construct(
        private TagCacheService $cache,
    ) {}

    public function execute(): Collection
    {
        $vaultId = Auth::user()->vault_id;

        return $this->cache->remember(function () use ($vaultId) {
            return Tag::query()
                ->where('vault_id', $vaultId)
                ->withCount('contacts')
                ->orderBy('name')
                ->get();
        });
    }
}
