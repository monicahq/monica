<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateVault;
use App\Actions\DestroyVault;
use App\Actions\SetDefaultVault;
use App\Actions\SetVaultDefaultTemplate;
use App\Actions\UpdateVault;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\VaultColourEnum;
use App\Http\Controllers\Controller;
use App\Jobs\RecordVaultVisit;
use App\Models\TemplateVersion;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultDashboardViewModel;
use App\ViewModels\Vaults\VaultFormViewModel;
use App\ViewModels\Vaults\VaultsViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VaultController extends Controller
{
    public function index(Request $request): View
    {
        return view('app.vaults.index', [
            'viewModel' => new VaultsViewModel(
                user: $request->user(),
            ),
        ]);
    }

    public function show(Request $request, Vault $vault): View
    {
        RecordVaultVisit::dispatch(vault: $vault)->onQueue('low');

        return view('app.vaults.show', [
            'viewModel' => new VaultDashboardViewModel(
                user: $request->user(),
                vault: $vault,
            ),
        ]);
    }

    public function new(Request $request): View
    {
        return view('app.vaults.form', [
            'viewModel' => new VaultFormViewModel(
                user: $request->user(),
            ),
        ]);
    }

    /**
     * Open the vault, then settle the two things the form asks about it rather
     * than about its wording: which template its contacts start from, and
     * whether it is where records land when nobody says.
     *
     * Both are their own action, because both are decisions about the account
     * as a whole. Creating the vault already gives it the supplied template, so
     * the first of them only runs when somebody picked another.
     */
    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));

        $vault = new CreateVault(
            user: $request->user(),
            name: $validated['name'],
            description: $validated['description'] ?? null,
            colour: $validated['colour'],
        )->execute();

        $this->settleWhatContactsStartFrom($request, $vault);

        if ($request->boolean('is_default')) {
            new SetDefaultVault(user: $request->user(), vault: $vault)->execute();
        }

        return redirect()->route('vaults.index')
            ->with('status', __('The vault is created.'))
            ->with('status_description', __('Everything you put in it stays in it.'));
    }

    /**
     * The wording of the vault, and nothing else about it. What it holds, where
     * it sits in the list and whether it is the default one are each settled
     * somewhere else.
     */
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

    /**
     * Somebody who deleted their last vault has an account holding nothing,
     * which is the state every account starts in, so they are taken back to
     * the screen that explains what to do first rather than to a list with
     * nothing on it.
     */
    public function destroy(Request $request, Vault $vault): RedirectResponse
    {
        new DestroyVault(
            user: $request->user(),
            vault: $vault,
        )->execute();

        $anyLeft = Vault::query()
            ->where('account_id', $request->user()->account_id)
            ->exists();

        if (! $anyLeft) {
            return redirect()->route('gettingStarted.index')
                ->with('status', __('The vault is deleted.'))
                ->with('status_description', __('Your account holds nothing now. The next vault you make is where things land.'));
        }

        return redirect()->route('vaults.index')
            ->with('status', __('The vault is deleted.'))
            ->with('status_description', __('Everything it held went with it.'));
    }

    /**
     * The template is offered as the published version of one of the account's
     * contact templates, which is exactly what the vault stores, so the rule
     * asks the templates table for it rather than trusting the select.
     *
     * @return array<string, array<int, mixed>>
     */
    private function rules(Request $request): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'colour' => ['required', 'string', Rule::in(VaultColourEnum::values())],
            'is_default' => ['nullable', 'boolean'],
            'template_version_id' => [
                'nullable',
                'integer',
                Rule::exists('templates', 'published_version_id')
                    ->where('account_id', $request->user()->account_id)
                    ->where('applies_to', TemplateAppliesToEnum::Contact->value)
                    ->where('is_active', true),
            ],
        ];
    }

    private function settleWhatContactsStartFrom(Request $request, Vault $vault): void
    {
        if (! $request->filled('template_version_id')) {
            return;
        }

        new SetVaultDefaultTemplate(
            user: $request->user(),
            vault: $vault,
            templateVersion: TemplateVersion::query()->findOrFail($request->integer('template_version_id')),
        )->execute();
    }
}
