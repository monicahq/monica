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
            'grouping' => ['required', 'string', Rule::enum(ContactGroupingEnum::class)],
            'sort' => ['required', 'string', Rule::enum(ContactSortEnum::class)],
            'direction' => ['required', 'string', Rule::enum(SortDirectionEnum::class)],
        ]);

        new UpdateContactListView(
            user: $request->user(),
            grouping: ContactGroupingEnum::from($validated['grouping']),
            sort: ContactSortEnum::from($validated['sort']),
            direction: SortDirectionEnum::from($validated['direction']),
        )->execute();

        return new UserResource($request->user()->refresh());
    }
}
