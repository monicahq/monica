<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Helpers\ChangelogEntries;
use App\Helpers\ChangelogEntry;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChangelogEntryResource;
use App\Models\ChangelogVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ChangelogEntryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $period = ChangelogEntries::period($request->string('period')->toString());
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $changelog = app(ChangelogEntries::class);
        $entries = $changelog->publishedWithin($period);
        $page = new LengthAwarePaginator(
            items: array_slice($entries, ($request->integer('page', 1) - 1) * $perPage, $perPage),
            total: count($entries),
            perPage: $perPage,
            currentPage: max(1, $request->integer('page', 1)),
        );

        $ids = array_map(fn (ChangelogEntry $entry): string => $entry->id, $page->items());
        $unseen = array_map(fn (ChangelogEntry $entry): string => $entry->id, $changelog->unseenBy($user));
        $counts = ChangelogEntries::commentCounts($ids);
        $votes = ChangelogVote::query()->where('user_id', $user->id)->whereIn('entry_id', $ids)->pluck('direction', 'entry_id');

        $rows = array_map(fn (ChangelogEntry $entry): array => [
            'entry' => $entry,
            'isSeen' => ! in_array($entry->id, $unseen, true),
            'myVote' => $votes->get($entry->id)?->value,
            'commentCount' => $counts[$entry->id] ?? 0,
        ], $page->items());

        return response()->json([
            'data' => ChangelogEntryResource::collection($rows)->resolve(),
            'meta' => [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'period' => $period,
                'unseen_count' => count($unseen),
                ...ChangelogEntries::figures($entries),
            ],
        ]);
    }
}
