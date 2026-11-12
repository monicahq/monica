<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\AttachPossessionValuationDocument;
use App\Actions\CreatePossessionValuation;
use App\Actions\DestroyPossessionValuation;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionValuationConfidenceEnum;
use App\Enums\PossessionValuationTypeEnum;
use App\Exceptions\NotEnoughStorageLeft;
use App\Helpers\FlagState;
use App\Http\Controllers\Controller;
use App\Models\Possession;
use App\Models\PossessionValuation;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionValuationViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PossessionValuationController extends Controller
{
    public function index(Request $request, Vault $vault, Possession $possession): View
    {
        return view('app.vaults.possessions.valuations', [
            'viewModel' => new PossessionValuationViewModel(
                user: $request->user(),
                possession: $possession,
            ),
        ]);
    }

    public function create(Request $request, Vault $vault, Possession $possession): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::enum(PossessionValuationTypeEnum::class)],
            'valued_on' => ['required', 'date'],
            'confidence' => ['required', Rule::enum(PossessionValuationConfidenceEnum::class)],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::enum(CurrencyEnum::class)],
            'valuer' => ['nullable', 'string', 'max:255'],
            'method' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
            'document' => ['nullable', 'file', 'max:'.(int) config('monica.max_file_size_in_kilobytes')],
        ]);

        $valuation = new CreatePossessionValuation(
            user: $request->user(),
            possession: $possession,
            type: PossessionValuationTypeEnum::from($validated['type']),
            valuedOn: CarbonImmutable::parse($validated['valued_on']),
            amount: (string) $validated['amount'],
            currency: CurrencyEnum::from($validated['currency']),
            confidence: PossessionValuationConfidenceEnum::from($validated['confidence']),
            valuer: $validated['valuer'] ?? null,
            method: $validated['method'] ?? null,
            referenceNumber: $validated['reference_number'] ?? null,
            sourceUrl: $validated['source_url'] ?? null,
            note: $validated['note'] ?? null,
        )->execute();

        if (isset($validated['document']) && FlagState::isOn('file-upload', $vault->account)) {
            try {
                new AttachPossessionValuationDocument(
                    user: $request->user(),
                    valuation: $valuation,
                    upload: $validated['document'],
                )->execute();
            } catch (NotEnoughStorageLeft $e) {
                return redirect()
                    ->route('possessions.valuations.index', [$vault, $possession])
                    ->withErrors(['document' => $e->getMessage()])
                    ->with('status', __('The valuation is recorded, without its document.'));
            }
        }

        return redirect()
            ->route('possessions.valuations.index', [$vault, $possession])
            ->with('status', __('The valuation is recorded.'));
    }

    public function destroy(Request $request, Vault $vault, Possession $possession, PossessionValuation $possessionValuation): RedirectResponse
    {
        abort_unless($possessionValuation->possession_id === $possession->id, 404);

        new DestroyPossessionValuation(
            user: $request->user(),
            valuation: $possessionValuation,
        )->execute();

        return redirect()
            ->route('possessions.valuations.index', [$vault, $possession])
            ->with('status', __('The valuation is deleted.'));
    }
}
