<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateValueList;
use App\Actions\DestroyValueList;
use App\Actions\UpdateValueList;
use App\Http\Controllers\Controller;
use App\Http\Resources\ValueListResource;
use App\Models\ValueList;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

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
            ->with('items')
            ->orderBy('position')
            ->paginate($perPage);

        return response()->json([
            'data' => ValueListResource::collection($lists->items())->resolve($request),
            'meta' => [
                'page' => $lists->currentPage(),
                'per_page' => $lists->perPage(),
                'total' => $lists->total(),
            ],
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $list = new CreateValueList(
            user: $request->user(),
            name: $validated['name'],
        )->execute();

        return new ValueListResource($list->load('items'))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $valueList): JsonResponse
    {
        $valueList = $this->find($request, $valueList);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        new UpdateValueList(
            user: $request->user(),
            valueList: $valueList,
            name: $validated['name'],
            description: $valueList->description,
            allowsUserValues: $valueList->allows_user_values,
        )->execute();

        return new ValueListResource($valueList->refresh()->load('items'))->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $valueList): Response
    {
        try {
            new DestroyValueList(
                user: $request->user(),
                valueList: $this->find($request, $valueList),
            )->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['valueList' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    private function find(Request $request, string $valueList): ValueList
    {
        return ValueList::query()
            ->where('account_id', $request->user()->account_id)
            ->where('is_shared', true)
            ->whereKey($valueList)
            ->firstOrFail();
    }
}
