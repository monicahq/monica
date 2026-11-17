<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateContactListView;
use App\Enums\ContactGroupingEnum;
use App\Enums\ContactSortEnum;
use App\Enums\SortDirectionEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeContactListViewController extends Controller
{
    public function update(Request $request): UserResource
    {
        $validated = $request->validate([
            'grouping' => ['sometimes', 'required', 'string', Rule::enum(ContactGroupingEnum::class)],
            'sort' => ['sometimes', 'required', 'string', Rule::enum(ContactSortEnum::class)],
            'direction' => ['sometimes', 'required', 'string', Rule::enum(SortDirectionEnum::class)],
        ]);

        new UpdateContactListView(
            user: $request->user(),
            grouping: ContactGroupingEnum::fromValueOrEveryone($validated['grouping'] ?? $request->user()->contact_list_grouping),
            sort: ContactSortEnum::fromValueOrName($validated['sort'] ?? $request->user()->contact_list_sort),
            direction: SortDirectionEnum::fromValueOrAscending($validated['direction'] ?? $request->user()->contact_list_direction),
        )->execute();

        return new UserResource($request->user()->refresh());
    }
}
