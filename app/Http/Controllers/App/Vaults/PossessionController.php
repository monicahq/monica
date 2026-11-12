<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreatePossession;
use App\Actions\DestroyPossession;
use App\Actions\UpdatePossession;
use App\Enums\CurrencyEnum;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionType;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionFormViewModel;
use App\ViewModels\Vaults\PossessionsViewModel;
use App\ViewModels\Vaults\PossessionViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PossessionController extends Controller
{
    public function index(Request $request, Vault $vault): View
    {
        return view('app.vaults.possessions.index', [
            'viewModel' => new PossessionsViewModel(
                user: $request->user(),
                vault: $vault,
            ),
        ]);
    }

    public function new(Request $request, Vault $vault, ?PossessionType $possessionType = null): View
    {
        return view('app.vaults.possessions.form', [
            'viewModel' => new PossessionFormViewModel(
                user: $request->user(),
                vault: $vault,
                chosenType: $possessionType,
            ),
        ]);
    }

    public function create(Request $request, Vault $vault): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', 'uuid'],
            'values' => ['nullable', 'array'],
            'location' => ['nullable', 'uuid'],
            'declared_value' => ['nullable', 'numeric'],
            'declared_value_currency' => ['nullable', Rule::enum(CurrencyEnum::class)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['nullable', 'string'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $possessionType = PossessionType::query()
            ->where('account_id', $request->user()->account_id)
            ->where('uuid', $validated['type'])
            ->first();

        if ($possessionType === null) {
            return back()->withInput()->withErrors(['type' => __('Choose a type.')]);
        }

        $location = null;

        if (isset($validated['location'])) {
            $location = Location::query()->where('account_id', $request->user()->account_id)->where('uuid', $validated['location'])->first();

            if ($location === null) {
                return back()->withInput()->withErrors(['location' => __('This location does not exist anymore.')]);
            }
        }

        $possession = new CreatePossession(
            user: $request->user(),
            vault: $vault,
            possessionType: $possessionType,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            location: $location,
            isFeatured: $request->boolean('is_featured'),
            declaredValue: isset($validated['declared_value']) ? (string) $validated['declared_value'] : null,
            declaredValueCurrency: CurrencyEnum::tryFrom($validated['declared_value_currency'] ?? ''),
            values: $validated['values'] ?? [],
            tagNames: $validated['tags'] ?? [],
        )->execute();

        return redirect()
            ->route('possessions.index', $vault)
            ->with('status', __(':name is written down.', ['name' => $possession->name]));
    }

    public function show(Request $request, Vault $vault, Possession $possession): View
    {
        return view('app.vaults.possessions.show', [
            'viewModel' => new PossessionViewModel(
                user: $request->user(),
                possession: $possession,
            ),
        ]);
    }

    public function edit(Request $request, Vault $vault, Possession $possession): View
    {
        return view('app.vaults.possessions.form', [
            'viewModel' => new PossessionFormViewModel(
                user: $request->user(),
                vault: $vault,
                possession: $possession,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault, Possession $possession): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'values' => ['nullable', 'array'],
            'location' => ['nullable', 'uuid'],
            'declared_value' => ['nullable', 'numeric'],
            'declared_value_currency' => ['nullable', Rule::enum(CurrencyEnum::class)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['nullable', 'string'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $location = null;

        if (isset($validated['location'])) {
            $location = Location::query()->where('account_id', $request->user()->account_id)->where('uuid', $validated['location'])->first();

            if ($location === null) {
                return back()->withInput()->withErrors(['location' => __('This location does not exist anymore.')]);
            }
        }

        new UpdatePossession(
            user: $request->user(),
            possession: $possession,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            location: $location,
            identifier: $possession->identifier,
            status: $possession->status,
            quantity: $possession->quantity,
            isFeatured: $request->boolean('is_featured'),
            declaredValue: isset($validated['declared_value']) ? (string) $validated['declared_value'] : null,
            declaredValueCurrency: CurrencyEnum::tryFrom($validated['declared_value_currency'] ?? ''),
            tagNames: $validated['tags'] ?? [],
            values: $validated['values'] ?? [],
        )->execute();

        return redirect()
            ->route('possessions.show', ['vault' => $vault, 'possession' => $possession])
            ->with('status', __(':name is saved.', ['name' => $possession->name]));
    }

    public function destroy(Request $request, Vault $vault, Possession $possession): RedirectResponse
    {
        new DestroyPossession(
            user: $request->user(),
            possession: $possession,
        )->execute();

        return redirect()
            ->route('possessions.index', $vault)
            ->with('status', __(':name is deleted.', ['name' => $possession->name]));
    }
}
