<?php

namespace App\Services\Tags;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TagCacheService
{
    private const TTL = 600;

    public function key(): string
    {
        return 'tags:vault:'.Auth::user()->vault_id.':list';
    }

    public function remember(callable $callback): mixed
    {
        return $this->store()->remember($this->key(), self::TTL, $callback);
    }

    public function forget(): void
    {
        $this->store()->forget($this->key());
    }

    private function store(): Repository
    {
        return Cache::store(config('cache.tags_store', 'redis'));
    }
}
