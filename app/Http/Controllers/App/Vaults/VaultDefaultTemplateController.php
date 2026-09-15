<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\SetVaultDefaultTemplate;
use App\Enums\StructureAppliesToEnum;
use App\Enums\VersionStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Structure;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\Vault;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VaultDefaultTemplateController extends Controller
{
    public function update(Request $request, Vault $vault): RedirectResponse
    {
        $contactStructures = Structure::query()
            ->select('id')
            ->where('account_id', $request->user()->account_id)
            ->where('applies_to', StructureAppliesToEnum::Contact->value)
            ->where('owner_type', Template::class)
            ->whereIn('owner_id', Template::query()->select('id')->where('is_active', true));

        $validated = $request->validate([
            'structure_version_id' => [
                'required',
                'integer',
                Rule::exists('structure_versions', 'id')
                    ->where('account_id', $request->user()->account_id)
                    ->where('status', VersionStatusEnum::Published->value)
                    ->where(fn (Builder $query): Builder => $query->whereIn('structure_id', $contactStructures)),
            ],
        ]);

        new SetVaultDefaultTemplate(
            user: $request->user(),
            vault: $vault,
            structureVersion: StructureVersion::query()->findOrFail($validated['structure_version_id']),
        )->execute();

        return redirect()->route('vaults.settings.index', $vault)
            ->with('status', __('The template is saved.'))
            ->with('status_description', __('Contacts already in this vault keep the one they were written on.'));
    }
}
