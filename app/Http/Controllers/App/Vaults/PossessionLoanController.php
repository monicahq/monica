<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreatePossessionLoan;
use App\Actions\DestroyPossessionLoan;
use App\Actions\UpdatePossessionLoan;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionLoanStatusEnum;
use App\Exceptions\VaultHasNoContactDefault;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionLoan;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionLoansViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PossessionLoanController extends Controller
{
    public function index(Request $request, Vault $vault, Possession $possession): View
    {
        return view('app.vaults.possessions.loans', [
            'viewModel' => new PossessionLoansViewModel(
                user: $request->user(),
                possession: $possession,
            ),
        ]);
    }

    public function create(Request $request, Vault $vault, Possession $possession): RedirectResponse
    {
        $validated = $this->validated($request);
        $isCreatingContact = $request->boolean('new_contact');

        try {
            new CreatePossessionLoan(
                user: $request->user(),
                possession: $possession,
                direction: PossessionLoanDirectionEnum::from($validated['direction']),
                status: PossessionLoanStatusEnum::from($validated['status']),
                startedOn: CarbonImmutable::parse($validated['started_on']),
                contact: $isCreatingContact ? null : $this->contact($vault, $validated['contact_id'] ?? null),
                newContactFirstName: $isCreatingContact ? (string) ($validated['new_contact_first_name'] ?? '') : null,
                newContactLastName: $isCreatingContact ? ($validated['new_contact_last_name'] ?? null) : null,
                dueOn: isset($validated['due_on']) ? CarbonImmutable::parse($validated['due_on']) : null,
                returnedOn: isset($validated['returned_on']) ? CarbonImmutable::parse($validated['returned_on']) : null,
                conditionOut: $this->condition($request, $validated['condition_out'] ?? null),
                conditionBack: $this->condition($request, $validated['condition_back'] ?? null),
                depositAmount: $validated['deposit_amount'] ?? null,
                depositCurrency: CurrencyEnum::tryFrom((string) ($validated['deposit_currency'] ?? '')),
                purpose: $validated['purpose'] ?? null,
                isInProvenance: $request->boolean('is_in_provenance'),
            )->execute();
        } catch (VaultHasNoContactDefault) {
            return back()->withErrors(['new_contact_first_name' => __('This vault has no template for new contacts to start from. Choose one in the vault settings.')])->withInput();
        }

        return redirect()
            ->route('possessions.loans.index', [$vault, $possession])
            ->with('status', __('The loan is recorded.'));
    }

    public function edit(Request $request, Vault $vault, Possession $possession, PossessionLoan $possessionLoan): View
    {
        abort_unless($possessionLoan->possession_id === $possession->id, 404);

        return view('app.vaults.possessions.loans', [
            'viewModel' => new PossessionLoansViewModel(
                user: $request->user(),
                possession: $possession,
                editing: $possessionLoan,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault, Possession $possession, PossessionLoan $possessionLoan): RedirectResponse
    {
        abort_unless($possessionLoan->possession_id === $possession->id, 404);

        $validated = $this->validated($request);

        new UpdatePossessionLoan(
            user: $request->user(),
            loan: $possessionLoan,
            direction: PossessionLoanDirectionEnum::from($validated['direction']),
            status: PossessionLoanStatusEnum::from($validated['status']),
            startedOn: CarbonImmutable::parse($validated['started_on']),
            contact: $this->contact($vault, $validated['contact_id'] ?? null),
            dueOn: isset($validated['due_on']) ? CarbonImmutable::parse($validated['due_on']) : null,
            returnedOn: isset($validated['returned_on']) ? CarbonImmutable::parse($validated['returned_on']) : null,
            conditionOut: $this->condition($request, $validated['condition_out'] ?? null),
            conditionBack: $this->condition($request, $validated['condition_back'] ?? null),
            depositAmount: $validated['deposit_amount'] ?? null,
            depositCurrency: CurrencyEnum::tryFrom((string) ($validated['deposit_currency'] ?? '')),
            purpose: $validated['purpose'] ?? null,
            isInProvenance: $request->boolean('is_in_provenance'),
        )->execute();

        return redirect()
            ->route('possessions.loans.index', [$vault, $possession])
            ->with('status', __('The loan is changed.'));
    }

    public function destroy(Request $request, Vault $vault, Possession $possession, PossessionLoan $possessionLoan): RedirectResponse
    {
        abort_unless($possessionLoan->possession_id === $possession->id, 404);

        new DestroyPossessionLoan(
            user: $request->user(),
            loan: $possessionLoan,
        )->execute();

        return redirect()
            ->route('possessions.loans.index', [$vault, $possession])
            ->with('status', __('The loan is deleted.'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'direction' => ['required', Rule::enum(PossessionLoanDirectionEnum::class)],
            'status' => ['required', Rule::enum(PossessionLoanStatusEnum::class)],
            'contact_id' => ['nullable', 'uuid'],
            'new_contact_first_name' => ['nullable', 'string', 'max:255'],
            'new_contact_last_name' => ['nullable', 'string', 'max:255'],
            'started_on' => ['required', 'date_format:Y-m-d'],
            'due_on' => ['nullable', 'date_format:Y-m-d'],
            'returned_on' => ['nullable', 'date_format:Y-m-d'],
            'condition_out' => ['nullable', 'uuid'],
            'condition_back' => ['nullable', 'uuid'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'deposit_currency' => ['nullable', Rule::enum(CurrencyEnum::class)],
            'purpose' => ['nullable', 'string', 'max:5000'],
        ], [
            'started_on.required' => __('Say when the loan started.'),
        ]);
    }

    private function contact(Vault $vault, ?string $uuid): ?Contact
    {
        if ($uuid === null) {
            return null;
        }

        return Contact::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    private function condition(Request $request, ?string $uuid): ?PossessionCondition
    {
        if ($uuid === null) {
            return null;
        }

        return PossessionCondition::query()
            ->where('account_id', $request->user()->account_id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
