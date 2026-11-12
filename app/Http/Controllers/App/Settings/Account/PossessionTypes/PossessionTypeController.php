<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\PossessionTypes;

use App\Actions\CreatePossessionType;
use App\Actions\DestroyPossessionType;
use App\Actions\UpdatePossessionType;
use App\Enums\VaultColourEnum;
use App\Http\Controllers\Controller;
use App\Models\PossessionType;
use App\Models\Section;
use App\ViewModels\Settings\Account\PossessionTypes\PossessionTypesViewModel;
use App\ViewModels\Settings\Account\PossessionTypes\PossessionTypeViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PossessionTypeController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.possession-types.index', [
            'viewModel' => new PossessionTypesViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $possessionType = new CreatePossessionType(
            user: $request->user(),
        )->execute();

        return redirect()->route('settings.possessionTypes.edit', $possessionType);
    }

    public function edit(Request $request, PossessionType $possessionType): View
    {
        return view('app.settings.account.possession-types.edit', [
            'viewModel' => new PossessionTypeViewModel(
                user: $request->user(),
                possessionType: $possessionType,
            ),
        ]);
    }

    public function update(Request $request, PossessionType $possessionType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'colour' => ['required', Rule::enum(VaultColourEnum::class)],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
        ]);

        new UpdatePossessionType(
            user: $request->user(),
            possessionType: $possessionType,
            name: $validated['name'],
            colour: VaultColourEnum::from($validated['colour']),
            icon: $validated['icon'] ?? null,
        )->execute();

        return redirect()->route('settings.possessionTypes.edit', $possessionType)
            ->with('saved', __('Saved.'));
    }

    public function destroy(Request $request, PossessionType $possessionType): RedirectResponse
    {
        new DestroyPossessionType(
            user: $request->user(),
            possessionType: $possessionType,
        )->execute();

        return redirect()->route('settings.possessionTypes.index')
            ->with('status', __('“:name” is deleted.', ['name' => $possessionType->name]));
    }
}
