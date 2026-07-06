<?php

namespace App\Domains\Contact\ManageContact\Api\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ContactQuery
{
    /**
     * Apply filters to the contact query.
     */
    public static function apply(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('favorite'), function (Builder $q) use ($request) {
                self::filterByFavorite($q, $request->boolean('favorite'));
            })
            ->when($request->filled('search'), function (Builder $q) use ($request) {
                self::filterBySearch($q, $request->input('search'));
            });
    }

    /**
     * Filter contacts by favorite status.
     */
    protected static function filterByFavorite(Builder $query, bool $isFavorite): void
    {
        $query->where('is_favorite', $isFavorite);
    }

    /**
     * Filter contacts by search term.
     * Searches across first_name, last_name, middle_name, nickname, and maiden_name.
     */
    protected static function filterBySearch(Builder $query, string $searchTerm): void
    {
        $searchTerm = '%' . $searchTerm . '%';

        $query->where(function (Builder $q) use ($searchTerm) {
            $q->where('first_name', 'like', $searchTerm)
                ->orWhere('last_name', 'like', $searchTerm)
                ->orWhere('middle_name', 'like', $searchTerm)
                ->orWhere('nickname', 'like', $searchTerm)
                ->orWhere('maiden_name', 'like', $searchTerm);
        });
    }
}
