<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateValueListItem;
use App\Actions\DestroyValueListItem;
use App\Actions\UpdateValueListItem;
use App\Enums\ValueListEnum;
use App\Helpers\CountryCodes;
use App\Http\Controllers\Controller;
use App\Http\Resources\ValueListItemResource;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ValueListItemController extends Controller
{
    public function create(Request $request, string $valueList): JsonResponse
    {
        $valueList = $this->list($request, $valueList);
        $validated = $this->validated($request, $valueList);

        try {
            $item = new CreateValueListItem(
                user: $request->user(),
                valueList: $valueList,
                label: $validated['label'] ?? null,
                countryCode: $validated['country_code'] ?? null,
            )->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['valueList' => $e->getMessage()]);
        }

        return new ValueListItemResource($item->setRelation('valueList', $valueList))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $valueList, string $valueListItem): JsonResponse
    {
        $valueList = $this->list($request, $valueList);
        $item = $this->item($valueList, $valueListItem);
        $validated = $this->validated($request, $valueList);

        new UpdateValueListItem(
            user: $request->user(),
            item: $item,
            label: $validated['label'] ?? null,
            description: $item->description,
            colour: $item->colour,
            icon: $item->icon,
            countryCode: $validated['country_code'] ?? null,
        )->execute();

        return new ValueListItemResource($item->refresh()->setRelation('valueList', $valueList))->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $valueList, string $valueListItem): Response
    {
        $item = $this->item($this->list($request, $valueList), $valueListItem);

        try {
            new DestroyValueListItem(
                user: $request->user(),
                item: $item,
            )->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['valueListItem' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    /** @return array{label?: string|null, country_code?: string|null} */
    private function validated(Request $request, ValueList $valueList): array
    {
        $request->merge(['country_code' => mb_strtoupper($request->string('country_code')->trim()->value()) ?: null]);

        $holdsCountries = $valueList->key === ValueListEnum::Countries->value;

        return $request->validate([
            'label' => ['nullable', $holdsCountries ? 'required_without:country_code' : 'required', 'string', 'max:255'],
            'country_code' => $holdsCountries
                ? ['nullable', 'required_without:label', 'string', 'size:2', Rule::in(CountryCodes::known())]
                : ['prohibited'],
        ]);
    }

    private function list(Request $request, string $valueList): ValueList
    {
        return ValueList::query()
            ->where('account_id', $request->user()->account_id)
            ->where('is_shared', true)
            ->whereKey($valueList)
            ->firstOrFail();
    }

    private function item(ValueList $valueList, string $valueListItem): ValueListItem
    {
        return $valueList->items()->whereKey($valueListItem)->firstOrFail();
    }
}
