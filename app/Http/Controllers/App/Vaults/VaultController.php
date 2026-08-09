<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateVault;
use App\Actions\SetDefaultVault;
use App\Actions\SetVaultDefaultTemplate;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\VaultColourEnum;
use App\Http\Controllers\Controller;
use App\Models\TemplateVersion;
use App\Models\Vault;
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

        $this->settleWhatContactsStartFrom($request, $vault, $validated['template_version_id'] ?? null);

        if ($request->boolean('is_default')) {
            new SetDefaultVault(user: $request->user(), vault: $vault)->execute();
        }

        return redirect()->route('vaults.index')
            ->with('status', __('The vault is created.'))
            ->with('status_description', __('Everything you put in it stays in it.'));
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

    private function settleWhatContactsStartFrom(Request $request, Vault $vault, ?int $versionId): void
    {
        if ($versionId === null) {
            return;
        }

        new SetVaultDefaultTemplate(
            user: $request->user(),
            vault: $vault,
            templateVersion: TemplateVersion::query()->findOrFail($versionId),
        )->execute();
    }
}
