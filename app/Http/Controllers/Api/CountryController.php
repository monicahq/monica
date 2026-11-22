<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ValueListEnum;
use App\Helpers\CountryCodes;
use App\Http\Controllers\Controller;
use App\Models\ValueListItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favourites = ValueListItem::query()
            ->where('account_id', $request->user()->account_id)
            ->whereRelation('valueList', 'key', ValueListEnum::Countries->value)
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->filter(fn (ValueListItem $item): bool => $item->countryCode() !== null);

        $favouriteIds = $favourites
            ->filter(fn (ValueListItem $item): bool => $item->readsAsItsCountry())
            ->mapWithKeys(fn (ValueListItem $item): array => [$item->countryCode() => $item->id]);
        $names = CountryCodes::all(app()->getLocale());

        $favouriteEntries = $favourites->map(fn (ValueListItem $item): array => [
            'code' => $item->countryCode(),
            'name' => $item->label,
            'favourite_id' => $item->id,
        ]);

        $everyCountry = collect($names)->map(fn (string $name, string $code): array => [
            'code' => $code,
            'name' => $name,
            'favourite_id' => $favouriteIds[$code] ?? null,
        ]);

        return response()->json([
            'data' => [...$favouriteEntries->values()->all(), ...$everyCountry->values()->all()],
        ]);
    }
}
