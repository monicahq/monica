<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\SetVaultDefaultTemplate;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\VersionStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\Vault;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VaultDefaultTemplateController extends Controller
{
    public function update(Request $request, Vault $vault): RedirectResponse
    {
        $contactTemplates = Template::query()
            ->select('id')
            ->where('account_id', $request->user()->account_id)
            ->where('applies_to', TemplateAppliesToEnum::Contact->value)
            ->where('is_active', true);

        $validated = $request->validate([
            'template_version_id' => [
                'required',
                'integer',
                Rule::exists('template_versions', 'id')
                    ->where('account_id', $request->user()->account_id)
                    ->where('status', VersionStatusEnum::Published->value)
                    ->where(fn (Builder $query): Builder => $query->whereIn('template_id', $contactTemplates)),
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
