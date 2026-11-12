<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Locations;

use App\Actions\CreateLocation;
use App\Actions\DestroyLocation;
use App\Actions\UpdateLocation;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\User;
use App\Rules\SingleEmoji;
use App\ViewModels\Settings\Account\Locations\LocationsViewModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class LocationController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.locations.index', [
            'viewModel' => new LocationsViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function new(Request $request): View
    {
        return view('app.settings.account.locations.index', [
            'viewModel' => new LocationsViewModel(
                user: $request->user(),
                creating: true,
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules($request->user()));

        $location = new CreateLocation(
            user: $request->user(),
            name: $validated['name'],
            parent: $this->parent($request->user(), $validated['parent'] ?? null),
            emoji: $validated['emoji'] ?? null,
            address: $validated['address'] ?? null,
        )->execute();

        return redirect()->route('settings.locations.index')
            ->with('status', __('“:name” is added.', ['name' => $location->name]));
    }

    public function edit(Request $request, Location $location): View
    {
        return view('app.settings.account.locations.index', [
            'viewModel' => new LocationsViewModel(
                user: $request->user(),
                editing: $location,
            ),
        ]);
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate($this->rules($request->user()));

        try {
            new UpdateLocation(
                user: $request->user(),
                location: $location,
                name: $validated['name'],
                parent: $this->parent($request->user(), $validated['parent'] ?? null),
                emoji: $validated['emoji'] ?? null,
                address: $validated['address'] ?? null,
            )->execute();
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['parent' => $e->getMessage()]);
        }

        return redirect()->route('settings.locations.index')
            ->with('status', __('“:name” is saved.', ['name' => $location->name]));
    }

    public function destroy(Request $request, Location $location): RedirectResponse
    {
        try {
            new DestroyLocation(
                user: $request->user(),
                location: $location,
            )->execute();
        } catch (AuthorizationException $e) {
            return redirect()->route('settings.locations.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('settings.locations.index')
            ->with('status', __('“:name” is deleted.', ['name' => $location->name]));
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent' => ['bail', 'nullable', 'uuid', Rule::exists('locations', 'uuid')->where('account_id', $user->account_id)],
            'emoji' => ['nullable', 'string', 'max:32', new SingleEmoji],
            'address' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function parent(User $user, ?string $uuid): ?Location
    {
        if ($uuid === null) {
            return null;
        }

        return $user->account->locations()->where('uuid', $uuid)->firstOrFail();
    }
}
