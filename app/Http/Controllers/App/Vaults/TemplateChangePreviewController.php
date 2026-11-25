<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateTemplateChange;
use App\Helpers\RecordKind;
use App\Http\Controllers\Controller;
use App\Models\EntityType;
use App\Models\Vault;
use App\ViewModels\Vaults\TemplateChangePreviewViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateChangePreviewController extends Controller
{
    public function show(Request $request, Vault $vault, ?EntityType $entityType = null): View|RedirectResponse
    {
        $kind = $this->kind($vault, $entityType);

        $validated = $request->validate([
            'from' => ['required', 'string', 'uuid'],
            'version' => ['nullable', 'integer', 'min:1'],
            'to' => ['required', 'string', 'uuid'],
        ]);

        $source = $kind->template($validated['from']);
        $target = $kind->template($validated['to']);
        $refusal = CreateTemplateChange::refusal($kind, $source, $target);

        if ($refusal !== null) {
            return redirect()->to($kind->route('templateChanges.new', ['from' => $source->uuid]))
                ->withErrors(['to' => $refusal]);
        }

        return view('app.vaults.template-changes.preview', [
            'viewModel' => new TemplateChangePreviewViewModel(
                user: $request->user(),
                kind: $kind,
                source: $source,
                versionNumber: isset($validated['version']) ? (int) $validated['version'] : null,
                target: $target,
            ),
        ]);
    }

    private function kind(Vault $vault, ?EntityType $entityType): RecordKind
    {
        if (! $entityType instanceof EntityType) {
            return RecordKind::forContacts(vault: $vault);
        }

        abort_unless(EntityType::query()->whereKey($entityType->id)->visibleInVault($vault)->exists(), 404);

        return RecordKind::forEntityType(vault: $vault, entityType: $entityType);
    }
}
