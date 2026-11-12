<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\PossessionConditions;

use App\Actions\CreatePossessionCondition;
use App\Actions\DestroyPossessionCondition;
use App\Actions\UpdatePossessionCondition;
use App\Enums\VaultColourEnum;
use App\Http\Controllers\Controller;
use App\Models\PossessionCondition;
use App\Models\Section;
use App\ViewModels\Settings\Account\PossessionConditions\PossessionConditionsViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PossessionConditionController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.possession-conditions.index', [
            'viewModel' => new PossessionConditionsViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('newPossessionCondition', $this->rules());

        try {
            $possessionCondition = new CreatePossessionCondition(
                user: $request->user(),
                name: $validated['name'],
                colour: VaultColourEnum::from($validated['colour']),
                icon: $validated['icon'] ?? null,
            )->execute();
        } catch (ValidationException $e) {
            throw $e->errorBag('newPossessionCondition');
        }

        return redirect()->route('settings.possessionConditions.index')
            ->with('status', __('“:name” is added.', ['name' => $possessionCondition->name]));
    }

    public function update(Request $request, PossessionCondition $possessionCondition): RedirectResponse
    {
        $bag = "possessionCondition{$possessionCondition->uuid}";
        $validated = $request->validateWithBag($bag, $this->rules());

        try {
            new UpdatePossessionCondition(
                user: $request->user(),
                possessionCondition: $possessionCondition,
                name: $validated['name'],
                colour: VaultColourEnum::from($validated['colour']),
                icon: $validated['icon'] ?? null,
            )->execute();
        } catch (ValidationException $e) {
            throw $e->errorBag($bag);
        }

        return redirect()->route('settings.possessionConditions.index')
            ->with('status', __('Saved.'));
    }

    public function destroy(Request $request, PossessionCondition $possessionCondition): RedirectResponse
    {
        new DestroyPossessionCondition(
            user: $request->user(),
            possessionCondition: $possessionCondition,
        )->execute();

        return redirect()->route('settings.possessionConditions.index')
            ->with('status', __('“:name” is deleted.', ['name' => $possessionCondition->name]));
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'colour' => ['required', Rule::enum(VaultColourEnum::class)],
            'icon' => ['nullable', 'string', Rule::in(Section::ICONS)],
        ];
    }
}
