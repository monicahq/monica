<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreatePossessionInsuranceRecord;
use App\Actions\DestroyPossessionInsuranceRecord;
use App\Actions\UpdatePossessionInsuranceRecord;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionInsuranceStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionInsuranceViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PossessionInsuranceRecordController extends Controller
{
    public function index(Request $request, Vault $vault, Possession $possession): View
    {
        return view('app.vaults.possessions.insurance', [
            'viewModel' => new PossessionInsuranceViewModel(
                user: $request->user(),
                possession: $possession,
            ),
        ]);
    }

    public function create(Request $request, Vault $vault, Possession $possession): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        new CreatePossessionInsuranceRecord(
            user: $request->user(),
            possession: $possession,
            insurer: $validated['insurer'],
            status: PossessionInsuranceStatusEnum::from($validated['status']),
            insuredValue: (string) $validated['insured_value'],
            currency: CurrencyEnum::from($validated['currency']),
            policyNumber: $validated['policy_number'] ?? null,
            coverage: $validated['coverage'] ?? null,
            excess: isset($validated['excess']) ? (string) $validated['excess'] : null,
            startsOn: isset($validated['starts_on']) ? CarbonImmutable::parse($validated['starts_on']) : null,
            endsOn: isset($validated['ends_on']) ? CarbonImmutable::parse($validated['ends_on']) : null,
            isListedIndividually: $request->boolean('is_listed_individually'),
            contactName: $validated['contact_name'] ?? null,
            contactEmail: $validated['contact_email'] ?? null,
            contactPhone: $validated['contact_phone'] ?? null,
            note: $validated['note'] ?? null,
        )->execute();

        return redirect()
            ->route('possessions.insuranceRecords.index', [$vault, $possession])
            ->with('status', __('The insurance record is added.'));
    }

    public function edit(Request $request, Vault $vault, Possession $possession, PossessionInsuranceRecord $possessionInsuranceRecord): View
    {
        abort_unless($possessionInsuranceRecord->possession_id === $possession->id, 404);

        return view('app.vaults.possessions.insurance', [
            'viewModel' => new PossessionInsuranceViewModel(
                user: $request->user(),
                possession: $possession,
                editing: $possessionInsuranceRecord,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault, Possession $possession, PossessionInsuranceRecord $possessionInsuranceRecord): RedirectResponse
    {
        abort_unless($possessionInsuranceRecord->possession_id === $possession->id, 404);

        $validated = $request->validate($this->rules());

        new UpdatePossessionInsuranceRecord(
            user: $request->user(),
            record: $possessionInsuranceRecord,
            insurer: $validated['insurer'],
            status: PossessionInsuranceStatusEnum::from($validated['status']),
            insuredValue: (string) $validated['insured_value'],
            currency: CurrencyEnum::from($validated['currency']),
            policyNumber: $validated['policy_number'] ?? null,
            coverage: $validated['coverage'] ?? null,
            excess: isset($validated['excess']) ? (string) $validated['excess'] : null,
            startsOn: isset($validated['starts_on']) ? CarbonImmutable::parse($validated['starts_on']) : null,
            endsOn: isset($validated['ends_on']) ? CarbonImmutable::parse($validated['ends_on']) : null,
            isListedIndividually: $request->boolean('is_listed_individually'),
            contactName: $validated['contact_name'] ?? null,
            contactEmail: $validated['contact_email'] ?? null,
            contactPhone: $validated['contact_phone'] ?? null,
            note: $validated['note'] ?? null,
        )->execute();

        return redirect()
            ->route('possessions.insuranceRecords.index', [$vault, $possession])
            ->with('status', __('The insurance record is changed.'));
    }

    public function destroy(Request $request, Vault $vault, Possession $possession, PossessionInsuranceRecord $possessionInsuranceRecord): RedirectResponse
    {
        abort_unless($possessionInsuranceRecord->possession_id === $possession->id, 404);

        new DestroyPossessionInsuranceRecord(
            user: $request->user(),
            record: $possessionInsuranceRecord,
        )->execute();

        return redirect()
            ->route('possessions.insuranceRecords.index', [$vault, $possession])
            ->with('status', __('The insurance record is deleted.'));
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'insurer' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(PossessionInsuranceStatusEnum::class)],
            'policy_number' => ['nullable', 'string', 'max:255'],
            'coverage' => ['nullable', 'string', 'max:255'],
            'insured_value' => ['required', 'numeric', 'min:0'],
            'excess' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', Rule::enum(CurrencyEnum::class)],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date'],
            'is_listed_individually' => ['nullable', 'boolean'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
