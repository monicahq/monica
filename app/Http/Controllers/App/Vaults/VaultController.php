<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateVault;
use App\Actions\DestroyVault;
use App\Actions\SetDefaultVault;
use App\Actions\UpdateVault;
use App\Enums\VaultColourEnum;
use App\Exceptions\VaultHasNoContactDefault;
use App\Helpers\AlpineAjaxTargets;
use App\Http\Controllers\Controller;
use App\Jobs\RecordVaultVisit;
use App\Models\StructureVersion;
use App\Models\Vault;
use App\Rules\PublishedContactStructureVersion;
use App\ViewModels\Vaults\VaultDashboardViewModel;
use App\ViewModels\Vaults\VaultFormViewModel;
use App\ViewModels\Vaults\VaultsViewModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VaultController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->user()->shows_vault_list) {
            $defaultVault = Vault::query()
                ->where('account_id', $request->user()->account_id)
                ->where('is_default', true)
                ->first();

            if ($defaultVault !== null) {
                $request->session()->reflash();

                return redirect()->route('vaults.show', $defaultVault);
            }
        }

        return view('app.vaults.index', [
            'viewModel' => new VaultsViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function show(Request $request, Vault $vault): View
    {
        $viewModel = new VaultDashboardViewModel(
            user: $request->user(),
            vault: $vault,
            lifeEventJustAddedId: $request->session()->get('lifeEventJustAdded'),
            timelineSlice: $request->string('slice')->toString() ?: null,
            timelineContact: $request->string('contact')->toString() ?: null,
            timelineType: $request->string('type')->toString() ?: null,
            timelinePage: max(1, $request->integer('page', 1)),
        );

        $targets = AlpineAjaxTargets::from($request);

        if ($targets !== [] && array_all($targets, fn (string $target): bool => str_starts_with($target, 'dashboard-timeline'))) {
            return view('app.vaults._timeline', ['timeline' => $viewModel->timeline()]);
        }

        RecordVaultVisit::dispatch(vault: $vault)->onQueue('low');

        return view('app.vaults.show', ['viewModel' => $viewModel]);
    }

    public function new(Request $request): View
    {
        return view('app.vaults.form', [
            'viewModel' => new VaultFormViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));

        try {
            $vault = new CreateVault(
                user: $request->user(),
                name: $validated['name'],
                description: $validated['description'] ?? null,
                colour: $validated['colour'],
                structureVersion: $request->filled('structure_version_id')
                    ? StructureVersion::query()->findOrFail($request->integer('structure_version_id'))
                    : null,
            )->execute();
        } catch (AuthorizationException $e) {
            return back()->withErrors(['name' => $e->getMessage()])->withInput();
        } catch (VaultHasNoContactDefault) {
            return back()->withErrors(['name' => __('This account has no template for contacts to start from, so the vault cannot be created.')])->withInput();
        }

        if ($request->boolean('is_default')) {
            new SetDefaultVault(user: $request->user(), vault: $vault)->execute();
        }

        $destination = $request->user()->shows_vault_list
            ? route('vaults.index')
            : route('vaults.show', $vault);

        return redirect($destination)
            ->with('status', __('The vault is created.'))
            ->with('status_description', __('Everything you put in it stays in it.'));
    }

    public function update(Request $request, Vault $vault): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'colour' => ['required', 'string', Rule::in(VaultColourEnum::values())],
        ]);

        new UpdateVault(
            user: $request->user(),
            vault: $vault,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            colour: $validated['colour'],
        )->execute();

        return redirect()->route('vaults.settings.index', $vault)
            ->with('status', __('The vault is saved.'));
    }

    public function destroy(Request $request, Vault $vault): RedirectResponse
    {
        try {
            new DestroyVault(
                user: $request->user(),
                vault: $vault,
            )->execute();
        } catch (AuthorizationException $e) {
            return back()->withErrors(['vault' => $e->getMessage()]);
        }

        $anyLeft = Vault::query()
            ->where('account_id', $request->user()->account_id)
            ->exists();

        if (! $anyLeft) {
            return redirect()->route('vaults.index')
                ->with('status', __('The vault is deleted.'))
                ->with('status_description', __('Your account holds nothing now. New records go to the next vault you make.'));
        }

        return redirect()->route('vaults.index')
            ->with('status', __('The vault is deleted.'))
            ->with('status_description', __('Everything it held was deleted with it.'));
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(Request $request): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'colour' => ['required', 'string', Rule::in(VaultColourEnum::values())],
            'is_default' => ['nullable', 'boolean'],
            'structure_version_id' => [
                'nullable',
                'integer',
                new PublishedContactStructureVersion($request->user()->account_id),
            ],
        ];
    }
}
