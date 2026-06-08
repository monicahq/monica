<?php

namespace App\Services\Contacts;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ListContactsService
{
    public function execute(array $tagIds = []): Collection
    {
        $vaultId = Auth::user()->vault_id;
        $tagIds = array_values(array_unique(array_map('intval', $tagIds)));

        if (count($tagIds) > 0) {
            $validCount = Tag::query()
                ->where('vault_id', $vaultId)
                ->whereIn('id', $tagIds)
                ->count();

            if ($validCount !== count($tagIds)) {
                throw ValidationException::withMessages([
                    'tags' => 'One or more tags do not belong to this vault.',
                ]);
            }
        }

        $query = Contact::query()
            ->where('vault_id', $vaultId)
            ->where('listed', true);

        if (count($tagIds) > 0) {
            $query
                ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds))
                ->withCount(['tags as matched_tags_count' => fn ($q) => $q->whereIn('tags.id', $tagIds)])
                ->having('matched_tags_count', '=', count($tagIds));
        }

        return $query->orderBy('last_updated_at', 'desc')->get();
    }
}
