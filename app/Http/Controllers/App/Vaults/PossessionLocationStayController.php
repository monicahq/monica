<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\DestroyPossessionLocationStay;
use App\Actions\MovePossession;
use App\Actions\UpdatePossessionLocationStay;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionLocationStay;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionLocationsViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PossessionLocationStayController extends Controller
{
    public function index(Request $request, Vault $vault, Possession $possession): View
    {
        return view('app.vaults.possessions.locations', [
            'viewModel' => new PossessionLocationsViewModel(
                user: $request->user(),
                possession: $possession,
            ),
        ]);
    }

    public function create(Request $request, Vault $vault, Possession $possession): RedirectResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'uuid', Rule::exists('locations', 'uuid')->where('account_id', $request->user()->account_id)],
            'moved_on' => ['required', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
        ], [
            'location_id.required' => __('Choose where it went.'),
            'moved_on.required' => __('Enter the day it moved.'),
        ]);

        new MovePossession(
            user: $request->user(),
            possession: $possession,
            location: $this->location($request, $validated['location_id']),
            movedOn: CarbonImmutable::parse($validated['moved_on']),
            reason: $validated['reason'] ?? null,
            note: $validated['note'] ?? null,
        )->execute();

        return redirect()
            ->route('possessions.locationStays.index', [$vault, $possession])
            ->with('status', __('The possession is moved.'));
    }

    public function edit(Request $request, Vault $vault, Possession $possession, PossessionLocationStay $possessionLocationStay): View
    {
        abort_unless($possessionLocationStay->possession_id === $possession->id, 404);

        return view('app.vaults.possessions.locations', [
            'viewModel' => new PossessionLocationsViewModel(
                user: $request->user(),
                possession: $possession,
                editing: $possessionLocationStay,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault, Possession $possession, PossessionLocationStay $possessionLocationStay): RedirectResponse
    {
        abort_unless($possessionLocationStay->possession_id === $possession->id, 404);

        $validated = $request->validate([
            'location_id' => ['required', 'uuid', Rule::exists('locations', 'uuid')->where('account_id', $request->user()->account_id)],
            'arrived_on' => ['nullable', 'date_format:Y-m-d'],
            'left_on' => ['nullable', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        new UpdatePossessionLocationStay(
            user: $request->user(),
            stay: $possessionLocationStay,
            location: $this->location($request, $validated['location_id']),
            arrivedOn: isset($validated['arrived_on']) ? CarbonImmutable::parse($validated['arrived_on']) : null,
            leftOn: isset($validated['left_on']) ? CarbonImmutable::parse($validated['left_on']) : null,
            reason: $validated['reason'] ?? null,
            note: $validated['note'] ?? null,
        )->execute();

        return redirect()
            ->route('possessions.locationStays.index', [$vault, $possession])
            ->with('status', __('The stay is changed.'));
    }

    public function destroy(Request $request, Vault $vault, Possession $possession, PossessionLocationStay $possessionLocationStay): RedirectResponse
    {
        abort_unless($possessionLocationStay->possession_id === $possession->id, 404);

        new DestroyPossessionLocationStay(
            user: $request->user(),
            stay: $possessionLocationStay,
        )->execute();

        return redirect()
            ->route('possessions.locationStays.index', [$vault, $possession])
            ->with('status', __('The stay is deleted.'));
    }

    private function location(Request $request, string $uuid): Location
    {
        return Location::query()
            ->where('account_id', $request->user()->account_id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
