<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\DisableValueListItem;
use App\Actions\EnableValueListItem;
use App\Http\Controllers\Controller;
use App\Http\Resources\ValueListItemResource;
use App\Models\ValueList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ValueListItemActivationController extends Controller
{
    public function update(Request $request, string $valueList, string $valueListItem): JsonResponse
    {
        $valueList = ValueList::query()
            ->where('account_id', $request->user()->account_id)
            ->where('is_shared', true)
            ->whereKey($valueList)
            ->firstOrFail();
        $item = $valueList->items()->whereKey($valueListItem)->firstOrFail();

        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $action = $request->boolean('is_active')
            ? new EnableValueListItem(user: $request->user(), item: $item)
            : new DisableValueListItem(user: $request->user(), item: $item);

        try {
            $action->execute();
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['is_active' => $e->validator->errors()->first()]);
        }

        return new ValueListItemResource($item->refresh()->setRelation('valueList', $valueList))->response()->setStatusCode(200);
    }
}
