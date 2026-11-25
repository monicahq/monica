<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Helpers\FieldCompletion;
use App\Helpers\RecordKind;
use App\Http\Controllers\Controller;
use App\Models\EntityType;
use App\Models\Vault;
use App\ViewModels\Vaults\RecordStatisticsViewModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecordStatisticsController extends Controller
{
    public function index(Request $request, Vault $vault, ?EntityType $entityType = null): View
    {
        if ($entityType instanceof EntityType) {
            abort_unless(EntityType::query()->whereKey($entityType->id)->visibleInVault($vault)->exists(), 404);
        }

        $validated = $request->validate([
            'template' => ['nullable', 'string', 'uuid'],
            'version' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'string', 'in:'.FieldCompletion::LOWEST_FIRST.','.FieldCompletion::FORM_ORDER],
        ]);

        $kind = $entityType instanceof EntityType
            ? RecordKind::forEntityType(vault: $vault, entityType: $entityType)
            : RecordKind::forContacts(vault: $vault);

        return view('app.vaults.statistics.index', [
            'viewModel' => new RecordStatisticsViewModel(
                user: $request->user(),
                kind: $kind,
                completionTemplate: $validated['template'] ?? null,
                completionVersion: isset($validated['version']) ? (int) $validated['version'] : null,
                completionOrder: $validated['order'] ?? FieldCompletion::LOWEST_FIRST,
            ),
        ]);
    }
}
