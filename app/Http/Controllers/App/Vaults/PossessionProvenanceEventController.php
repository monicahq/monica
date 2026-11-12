<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreatePossessionProvenanceEvent;
use App\Actions\DestroyPossessionProvenanceEvent;
use App\Actions\UpdatePossessionProvenanceEvent;
use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Possession;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTransaction;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionProvenanceViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PossessionProvenanceEventController extends Controller
{
    public function index(Request $request, Vault $vault, Possession $possession): View
    {
        return view('app.vaults.possessions.provenance', [
            'viewModel' => new PossessionProvenanceViewModel(
                user: $request->user(),
                possession: $possession,
            ),
        ]);
    }

    public function create(Request $request, Vault $vault, Possession $possession): RedirectResponse
    {
        $validated = $request->validate($this->rules($possession));

        new CreatePossessionProvenanceEvent(
            user: $request->user(),
            possession: $possession,
            type: PossessionProvenanceEventTypeEnum::from($validated['type']),
            datePrecision: PossessionProvenanceDatePrecisionEnum::from($validated['date_precision']),
            occurredOn: $validated['occurred_on'] ?? null,
            title: $validated['title'] ?? null,
            from: $validated['from'] ?? null,
            to: $validated['to'] ?? null,
            location: $validated['location'] ?? null,
            description: $validated['description'] ?? null,
            referenceNumber: $validated['reference_number'] ?? null,
            sourceUrl: $validated['source_url'] ?? null,
            transaction: $this->transaction($possession, $validated['transaction'] ?? null),
            isVerified: $request->boolean('is_verified'),
        )->execute();

        return redirect()
            ->route('possessions.provenance.index', [$vault, $possession])
            ->with('status', __('The event is added to the provenance.'));
    }

    public function edit(Request $request, Vault $vault, Possession $possession, PossessionProvenanceEvent $possessionProvenanceEvent): View
    {
        abort_unless($possessionProvenanceEvent->possession_id === $possession->id, 404);

        return view('app.vaults.possessions.provenance', [
            'viewModel' => new PossessionProvenanceViewModel(
                user: $request->user(),
                possession: $possession,
                editing: $possessionProvenanceEvent,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault, Possession $possession, PossessionProvenanceEvent $possessionProvenanceEvent): RedirectResponse
    {
        abort_unless($possessionProvenanceEvent->possession_id === $possession->id, 404);

        $validated = $request->validate($this->rules($possession));

        new UpdatePossessionProvenanceEvent(
            user: $request->user(),
            event: $possessionProvenanceEvent,
            type: PossessionProvenanceEventTypeEnum::from($validated['type']),
            datePrecision: PossessionProvenanceDatePrecisionEnum::from($validated['date_precision']),
            occurredOn: $validated['occurred_on'] ?? null,
            title: $validated['title'] ?? null,
            from: $validated['from'] ?? null,
            to: $validated['to'] ?? null,
            location: $validated['location'] ?? null,
            description: $validated['description'] ?? null,
            referenceNumber: $validated['reference_number'] ?? null,
            sourceUrl: $validated['source_url'] ?? null,
            transaction: $this->transaction($possession, $validated['transaction'] ?? null),
            isVerified: $request->boolean('is_verified'),
        )->execute();

        return redirect()
            ->route('possessions.provenance.index', [$vault, $possession])
            ->with('status', __('The event is changed.'));
    }

    public function destroy(Request $request, Vault $vault, Possession $possession, PossessionProvenanceEvent $possessionProvenanceEvent): RedirectResponse
    {
        abort_unless($possessionProvenanceEvent->possession_id === $possession->id, 404);

        new DestroyPossessionProvenanceEvent(
            user: $request->user(),
            event: $possessionProvenanceEvent,
        )->execute();

        return redirect()
            ->route('possessions.provenance.index', [$vault, $possession])
            ->with('status', __('The event is deleted.'));
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(Possession $possession): array
    {
        return [
            'type' => ['required', Rule::enum(PossessionProvenanceEventTypeEnum::class)],
            'date_precision' => ['required', Rule::enum(PossessionProvenanceDatePrecisionEnum::class)],
            'occurred_on' => ['nullable', 'string', 'max:10'],
            'title' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'string', 'max:255'],
            'to' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'transaction' => ['nullable', 'string', Rule::exists('possession_transactions', 'uuid')->where('possession_id', $possession->id)],
        ];
    }

    private function transaction(Possession $possession, ?string $uuid): ?PossessionTransaction
    {
        if ($uuid === null) {
            return null;
        }

        return PossessionTransaction::query()
            ->where('possession_id', $possession->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
