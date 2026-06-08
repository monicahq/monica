<?php

namespace App\Services\Tags;

use App\Models\Contact;
use App\Models\Tag;
use App\Services\Tags\Concerns\BelongsToVault;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AttachTagService
{
    use BelongsToVault;

    public function __construct(
        private TagCacheService $cache,
    ) {}

    public function execute(Contact $contact, array $tagIds): void
    {
        $this->ensureContactBelongsToVault($contact);

        $tagIds = array_values(array_unique(array_map('intval', $tagIds)));
        $vaultId = Auth::user()->vault_id;

        $validTagIds = Tag::query()
            ->where('vault_id', $vaultId)
            ->whereIn('id', $tagIds)
            ->pluck('id')
            ->all();

        if (count($validTagIds) !== count($tagIds)) {
            throw ValidationException::withMessages([
                'tag_ids' => 'One or more tags do not belong to this vault.',
            ]);
        }

        $contact->tags()->syncWithoutDetaching($validTagIds);

        $this->cache->forget();
    }
}
