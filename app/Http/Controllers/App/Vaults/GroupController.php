<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateGroup;
use App\Actions\DestroyGroup;
use App\Actions\UpdateGroup;
use App\Enums\DatePrecisionEnum;
use App\Helpers\PageSize;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Vault;
use App\ViewModels\Vaults\GroupFormViewModel;
use App\ViewModels\Vaults\GroupsViewModel;
use App\ViewModels\Vaults\GroupViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function index(Request $request, Vault $vault): View
    {
        return view('app.vaults.groups.index', [
            'viewModel' => new GroupsViewModel(
                user: $request->user(),
                vault: $vault,
                size: PageSize::from($request, GroupsViewModel::PER_PAGE),
                deletedGroup: $request->session()->get('deletedGroup'),
            ),
        ]);
    }

    public function new(Request $request, Vault $vault): View
    {
        return view('app.vaults.groups.form', [
            'viewModel' => new GroupFormViewModel(user: $request->user(), vault: $vault),
        ]);
    }

    public function create(Request $request, Vault $vault): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $group = new CreateGroup(
            user: $request->user(),
            vault: $vault,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            startedOn: isset($validated['started_on']) ? CarbonImmutable::parse($validated['started_on']) : null,
            startedPrecision: DatePrecisionEnum::from($validated['started_precision'] ?? DatePrecisionEnum::Day->value),
            endedOn: isset($validated['ended_on']) ? CarbonImmutable::parse($validated['ended_on']) : null,
            endedPrecision: DatePrecisionEnum::from($validated['ended_precision'] ?? DatePrecisionEnum::Day->value),
        )->execute();

        return redirect()->route('groups.show', ['vault' => $vault, 'group' => $group])
            ->with('status', __('The group :name is created.', ['name' => $group->name]));
    }

    public function show(Request $request, Vault $vault, Group $group): View
    {
        return view('app.vaults.groups.show', [
            'viewModel' => new GroupViewModel(
                user: $request->user(),
                vault: $vault,
                group: $group,
                size: PageSize::from($request, GroupViewModel::PER_PAGE),
            ),
        ]);
    }

    public function edit(Request $request, Vault $vault, Group $group): View
    {
        return view('app.vaults.groups.form', [
            'viewModel' => new GroupFormViewModel(user: $request->user(), vault: $vault, group: $group),
        ]);
    }

    public function update(Request $request, Vault $vault, Group $group): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        new UpdateGroup(
            user: $request->user(),
            vault: $vault,
            group: $group,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            startedOn: isset($validated['started_on']) ? CarbonImmutable::parse($validated['started_on']) : null,
            startedPrecision: DatePrecisionEnum::from($validated['started_precision'] ?? DatePrecisionEnum::Day->value),
            endedOn: isset($validated['ended_on']) ? CarbonImmutable::parse($validated['ended_on']) : null,
            endedPrecision: DatePrecisionEnum::from($validated['ended_precision'] ?? DatePrecisionEnum::Day->value),
        )->execute();

        return redirect()->route('groups.show', ['vault' => $vault, 'group' => $group])
            ->with('status', __('The group is saved.'));
    }

    public function destroy(Request $request, Vault $vault, Group $group): RedirectResponse
    {
        new DestroyGroup(
            user: $request->user(),
            vault: $vault,
            group: $group,
        )->execute();

        return redirect()->route('groups.index', $vault)
            ->with('deletedGroup', ['uuid' => $group->uuid, 'name' => $group->name]);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'started_on' => ['nullable', 'date_format:Y-m-d'],
            'started_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
            'ended_on' => ['nullable', 'date_format:Y-m-d'],
            'ended_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
        ];
    }
}
