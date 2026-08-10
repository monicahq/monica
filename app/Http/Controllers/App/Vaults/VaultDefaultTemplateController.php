<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\SetVaultDefaultTemplate;
use App\Enums\TemplateAppliesToEnum;
use App\Http\Controllers\Controller;
use App\Models\TemplateVersion;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VaultDefaultTemplateController extends Controller
{
    /**
     * The screen offers the published version of one of the account's contact
     * templates, which is exactly what the vault stores, so the rule asks the
     * templates table for it rather than trusting the select.
     */
    public function update(Request $request, Vault $vault): RedirectResponse
    {
        $validated = $request->validate([
            'template_version_id' => [
                'required',
                'integer',
                Rule::exists('templates', 'published_version_id')
                    ->where('account_id', $request->user()->account_id)
                    ->where('applies_to', TemplateAppliesToEnum::Contact->value)
                    ->where('is_active', true),
            ],
        ]);

        new SetVaultDefaultTemplate(
            user: $request->user(),
            vault: $vault,
            templateVersion: TemplateVersion::query()->findOrFail($validated['template_version_id']),
        )->execute();

        return redirect()->route('vaults.settings.index', $vault)
            ->with('status', __('The template is saved.'))
            ->with('status_description', __('Contacts already in this vault keep the one they were written on.'));
    }
}
