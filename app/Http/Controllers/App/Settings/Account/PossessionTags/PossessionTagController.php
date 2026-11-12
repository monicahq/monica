<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\PossessionTags;

use App\Actions\CreatePossessionTag;
use App\Actions\DestroyPossessionTag;
use App\Actions\UpdatePossessionTag;
use App\Enums\VaultColourEnum;
use App\Http\Controllers\Controller;
use App\Models\PossessionTag;
use App\ViewModels\Settings\Account\PossessionTags\PossessionTagsViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PossessionTagController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.settings.account.possession-tags.index', [
            'viewModel' => new PossessionTagsViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('newPossessionTag', $this->rules());

        try {
            $possessionTag = new CreatePossessionTag(
                user: $request->user(),
                name: $validated['name'],
                colour: VaultColourEnum::from($validated['colour']),
            )->execute();
        } catch (ValidationException $e) {
            throw $e->errorBag('newPossessionTag');
        }

        return redirect()->route('settings.possessionTags.index')
            ->with('status', __('“:name” is added.', ['name' => $possessionTag->name]));
    }

    public function update(Request $request, PossessionTag $possessionTag): RedirectResponse
    {
        $bag = "possessionTag{$possessionTag->uuid}";
        $validated = $request->validateWithBag($bag, $this->rules());

        try {
            new UpdatePossessionTag(
                user: $request->user(),
                possessionTag: $possessionTag,
                name: $validated['name'],
                colour: VaultColourEnum::from($validated['colour']),
            )->execute();
        } catch (ValidationException $e) {
            throw $e->errorBag($bag);
        }

        return redirect()->route('settings.possessionTags.index')
            ->with('status', __('Saved.'));
    }

    public function destroy(Request $request, PossessionTag $possessionTag): RedirectResponse
    {
        new DestroyPossessionTag(
            user: $request->user(),
            possessionTag: $possessionTag,
        )->execute();

        return redirect()->route('settings.possessionTags.index')
            ->with('status', __('“:name” is deleted.', ['name' => $possessionTag->name]));
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'colour' => ['required', Rule::enum(VaultColourEnum::class)],
        ];
    }
}
