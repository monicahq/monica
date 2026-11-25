<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateTemplateChange;
use App\Helpers\RecordKind;
use App\Http\Controllers\Controller;
use App\Models\EntityType;
use App\Models\TemplateChange;
use App\Models\Vault;
use App\ViewModels\Vaults\TemplateChangeFormViewModel;
use App\ViewModels\Vaults\TemplateChangeViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class TemplateChangeController extends Controller
{
    public function new(Request $request, Vault $vault, ?EntityType $entityType = null): View
    {
        $kind = $this->kind($vault, $entityType);

        $validated = $request->validate([
            'from' => ['required', 'string', 'uuid'],
            'version' => ['nullable', 'integer', 'min:1'],
            'to' => ['nullable', 'string', 'uuid'],
        ]);

        return view('app.vaults.template-changes.new', [
            'viewModel' => new TemplateChangeFormViewModel(
                user: $request->user(),
                kind: $kind,
                source: $kind->template($validated['from']),
                versionNumber: isset($validated['version']) ? (int) $validated['version'] : null,
                target: isset($validated['to']) ? $kind->template($validated['to']) : null,
            ),
        ]);
    }

    public function create(Request $request, Vault $vault, ?EntityType $entityType = null): RedirectResponse
    {
        $kind = $this->kind($vault, $entityType);

        $validated = $request->validate([
            'from' => ['required', 'string', 'uuid'],
            'version' => ['nullable', 'integer', 'min:1'],
            'to' => ['required', 'string', 'uuid'],
        ]);

        try {
            $templateChange = new CreateTemplateChange(
                user: $request->user(),
                kind: $kind,
                source: $kind->template($validated['from']),
                versionNumbers: isset($validated['version']) ? [(int) $validated['version']] : [],
                target: $kind->template($validated['to']),
            )->execute();
        } catch (InvalidArgumentException $e) {
            return redirect()->to($kind->route('templateChanges.new', array_filter([
                'from' => $validated['from'],
                'version' => $validated['version'] ?? null,
                'to' => $validated['to'],
            ])))->withErrors(['to' => $e->getMessage()]);
        }

        return redirect()->to($kind->route('templateChanges.show', ['templateChange' => $templateChange->uuid]));
    }

    public function show(Request $request, Vault $vault, ?EntityType $entityType = null): View
    {
        $kind = $this->kind($vault, $entityType);
        $templateChange = TemplateChange::query()
            ->where('account_id', $request->user()->account_id)
            ->where('vault_id', $vault->id)
            ->where('entity_type_id', $entityType?->id)
            ->where('uuid', (string) $request->route()->parameter('templateChange'))
            ->first();

        abort_unless($templateChange instanceof TemplateChange, 404);

        return view('app.vaults.template-changes.show', [
            'viewModel' => new TemplateChangeViewModel(
                user: $request->user(),
                kind: $kind,
                templateChange: $templateChange,
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
