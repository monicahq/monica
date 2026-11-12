<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreatePossessionMaintenanceRecord;
use App\Actions\DestroyPossessionMaintenanceRecord;
use App\Actions\UpdatePossessionMaintenanceRecord;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionMaintenanceTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionMaintenanceRecord;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionMaintenanceViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PossessionMaintenanceRecordController extends Controller
{
    public function index(Request $request, Vault $vault, Possession $possession): View
    {
        return view('app.vaults.possessions.maintenance', [
            'viewModel' => new PossessionMaintenanceViewModel(
                user: $request->user(),
                possession: $possession,
            ),
        ]);
    }

    public function create(Request $request, Vault $vault, Possession $possession): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        new CreatePossessionMaintenanceRecord(
            user: $request->user(),
            possession: $possession,
            type: PossessionMaintenanceTypeEnum::from($validated['type']),
            title: $validated['title'],
            performedOn: CarbonImmutable::parse($validated['performed_on']),
            performedBy: $validated['performed_by'] ?? null,
            cost: isset($validated['cost']) ? (string) $validated['cost'] : null,
            currency: isset($validated['currency']) ? CurrencyEnum::from($validated['currency']) : null,
            conditionBefore: $this->condition($validated['condition_before'] ?? null),
            conditionAfter: $this->condition($validated['condition_after'] ?? null),
            nextDueOn: isset($validated['next_due_on']) ? CarbonImmutable::parse($validated['next_due_on']) : null,
            description: $validated['description'] ?? null,
        )->execute();

        return redirect()
            ->route('possessions.maintenanceRecords.index', [$vault, $possession])
            ->with('status', __('The maintenance record is added.'));
    }

    public function edit(Request $request, Vault $vault, Possession $possession, PossessionMaintenanceRecord $possessionMaintenanceRecord): View
    {
        abort_unless($possessionMaintenanceRecord->possession_id === $possession->id, 404);

        return view('app.vaults.possessions.maintenance', [
            'viewModel' => new PossessionMaintenanceViewModel(
                user: $request->user(),
                possession: $possession,
                editing: $possessionMaintenanceRecord,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault, Possession $possession, PossessionMaintenanceRecord $possessionMaintenanceRecord): RedirectResponse
    {
        abort_unless($possessionMaintenanceRecord->possession_id === $possession->id, 404);

        $validated = $request->validate($this->rules());

        new UpdatePossessionMaintenanceRecord(
            user: $request->user(),
            record: $possessionMaintenanceRecord,
            type: PossessionMaintenanceTypeEnum::from($validated['type']),
            title: $validated['title'],
            performedOn: CarbonImmutable::parse($validated['performed_on']),
            performedBy: $validated['performed_by'] ?? null,
            cost: isset($validated['cost']) ? (string) $validated['cost'] : null,
            currency: isset($validated['currency']) ? CurrencyEnum::from($validated['currency']) : null,
            conditionBefore: $this->condition($validated['condition_before'] ?? null),
            conditionAfter: $this->condition($validated['condition_after'] ?? null),
            nextDueOn: isset($validated['next_due_on']) ? CarbonImmutable::parse($validated['next_due_on']) : null,
            description: $validated['description'] ?? null,
        )->execute();

        return redirect()
            ->route('possessions.maintenanceRecords.index', [$vault, $possession])
            ->with('status', __('The maintenance record is changed.'));
    }

    public function destroy(Request $request, Vault $vault, Possession $possession, PossessionMaintenanceRecord $possessionMaintenanceRecord): RedirectResponse
    {
        abort_unless($possessionMaintenanceRecord->possession_id === $possession->id, 404);

        new DestroyPossessionMaintenanceRecord(
            user: $request->user(),
            record: $possessionMaintenanceRecord,
        )->execute();

        return redirect()
            ->route('possessions.maintenanceRecords.index', [$vault, $possession])
            ->with('status', __('The maintenance record is deleted.'));
    }

    private function condition(?string $uuid): ?PossessionCondition
    {
        if ($uuid === null) {
            return null;
        }

        return PossessionCondition::query()->where('uuid', $uuid)->firstOrFail();
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(PossessionMaintenanceTypeEnum::class)],
            'title' => ['required', 'string', 'max:255'],
            'performed_by' => ['nullable', 'string', 'max:255'],
            'performed_on' => ['required', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', Rule::enum(CurrencyEnum::class)],
            'condition_before' => ['nullable', 'uuid'],
            'condition_after' => ['nullable', 'uuid'],
            'next_due_on' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
