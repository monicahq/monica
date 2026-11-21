<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ValueListEnum;
use App\Http\Controllers\Controller;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ValueListController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $lists = ValueList::query()
            ->where('account_id', $request->user()->account_id)
            ->where('is_shared', true)
            ->with(['items' => fn ($query) => $query->orderBy('position')])
            ->orderBy('position')
            ->paginate($perPage);

        return response()->json([
            'data' => array_map(fn (ValueList $list): array => [
                'type' => 'value_list',
                'id' => $list->id,
                'attributes' => [
                    'name' => $list->name,
                    'items' => $list->items->map(fn (ValueListItem $item): array => [
                        'id' => $item->id,
                        'label' => $item->label,
                        'country_code' => $list->key === ValueListEnum::Countries->value && ($item->country_code ?? $item->key) !== null
                            ? mb_strtoupper((string) ($item->country_code ?? $item->key))
                            : null,
                    ])->all(),
                ],
            ], $lists->items()),
            'meta' => [
                'page' => $lists->currentPage(),
                'per_page' => $lists->perPage(),
                'total' => $lists->total(),
            ],
        ]);
    }
}
