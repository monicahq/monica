<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Helpers\ReminderDates;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReminderOccurrenceResource;
use App\Models\Group;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GroupReminderOccurrenceController extends Controller
{
    public function index(Request $request, string $vault, string $group): JsonResponse
    {
        $group = $this->group($this->vault($request, $vault), $group);

        $validated = $request->validate([
            'category' => ['nullable', 'string', Rule::in(ReminderDates::categories())],
        ]);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $paginated = ReminderDates::page(
            user: $request->user(),
            record: $group,
            category: $validated['category'] ?? null,
            perPage: $perPage,
            page: max(1, (int) $request->query('page', '1')),
        );

        return response()->json([
            'data' => ReminderOccurrenceResource::collection($paginated->items())->resolve(),
            'meta' => [
                'page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function group(Vault $vault, string $address): Group
    {
        return Group::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $address)
            ->firstOrFail();
    }
}
