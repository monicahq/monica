<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\AttachPossessionTransactionDocument;
use App\Actions\CreatePossessionTransaction;
use App\Actions\DestroyPossessionTransaction;
use App\Actions\UpdatePossessionTransaction;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionTransactionTypeEnum;
use App\Exceptions\NotEnoughStorageLeft;
use App\Helpers\FlagState;
use App\Http\Controllers\Controller;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\Vault;
use App\ViewModels\Vaults\PossessionTransactionsViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PossessionTransactionController extends Controller
{
    public function index(Request $request, Vault $vault, Possession $possession): View
    {
        return view('app.vaults.possessions.transactions', [
            'viewModel' => new PossessionTransactionsViewModel(
                user: $request->user(),
                possession: $possession,
            ),
        ]);
    }

    public function create(Request $request, Vault $vault, Possession $possession): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->rules(),
            'document' => ['nullable', 'file', 'max:'.(int) config('monica.max_file_size_in_kilobytes')],
        ]);
        $type = PossessionTransactionTypeEnum::from($validated['type']);

        $transaction = new CreatePossessionTransaction(
            user: $request->user(),
            possession: $possession,
            type: $type,
            occurredOn: isset($validated['occurred_on']) ? CarbonImmutable::parse($validated['occurred_on']) : null,
            counterparty: $validated['counterparty'] ?? null,
            amount: isset($validated['amount']) ? (string) $validated['amount'] : null,
            tax: isset($validated['tax']) ? (string) $validated['tax'] : null,
            fees: isset($validated['fees']) ? (string) $validated['fees'] : null,
            shipping: isset($validated['shipping']) ? (string) $validated['shipping'] : null,
            enteredTotal: isset($validated['entered_total']) ? (string) $validated['entered_total'] : null,
            currency: CurrencyEnum::tryFrom($validated['currency'] ?? ''),
            reference: $validated['reference'] ?? null,
            sourceUrl: $validated['source_url'] ?? null,
            note: $validated['note'] ?? null,
        )->execute();

        if (isset($validated['document']) && FlagState::isOn('file-upload', $vault->account)) {
            try {
                new AttachPossessionTransactionDocument(
                    user: $request->user(),
                    transaction: $transaction,
                    upload: $validated['document'],
                )->execute();
            } catch (NotEnoughStorageLeft $e) {
                return redirect()
                    ->route('possessions.transactions.index', [$vault, $possession])
                    ->withErrors(["document.{$transaction->uuid}" => $e->getMessage()])
                    ->with('status', __('The transaction is recorded, without its document.'));
            }
        }

        return redirect()
            ->route('possessions.transactions.index', [$vault, $possession])
            ->with('status', __('The transaction is recorded.'))
            ->with('transactionsView', $type->changesOwnership() ? null : 'all');
    }

    public function edit(Request $request, Vault $vault, Possession $possession, PossessionTransaction $possessionTransaction): View
    {
        abort_unless($possessionTransaction->possession_id === $possession->id, 404);

        return view('app.vaults.possessions.transactions', [
            'viewModel' => new PossessionTransactionsViewModel(
                user: $request->user(),
                possession: $possession,
                editing: $possessionTransaction,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault, Possession $possession, PossessionTransaction $possessionTransaction): RedirectResponse
    {
        abort_unless($possessionTransaction->possession_id === $possession->id, 404);

        $validated = $request->validate($this->rules());
        $type = PossessionTransactionTypeEnum::from($validated['type']);

        new UpdatePossessionTransaction(
            user: $request->user(),
            transaction: $possessionTransaction,
            type: $type,
            occurredOn: isset($validated['occurred_on']) ? CarbonImmutable::parse($validated['occurred_on']) : null,
            counterparty: $validated['counterparty'] ?? null,
            amount: isset($validated['amount']) ? (string) $validated['amount'] : null,
            tax: isset($validated['tax']) ? (string) $validated['tax'] : null,
            fees: isset($validated['fees']) ? (string) $validated['fees'] : null,
            shipping: isset($validated['shipping']) ? (string) $validated['shipping'] : null,
            enteredTotal: isset($validated['entered_total']) ? (string) $validated['entered_total'] : null,
            currency: CurrencyEnum::tryFrom($validated['currency'] ?? ''),
            reference: $validated['reference'] ?? null,
            sourceUrl: $validated['source_url'] ?? null,
            note: $validated['note'] ?? null,
        )->execute();

        return redirect()
            ->route('possessions.transactions.index', [$vault, $possession])
            ->with('status', __('The transaction is changed.'))
            ->with('transactionsView', $type->changesOwnership() ? null : 'all');
    }

    public function destroy(Request $request, Vault $vault, Possession $possession, PossessionTransaction $possessionTransaction): RedirectResponse
    {
        abort_unless($possessionTransaction->possession_id === $possession->id, 404);

        new DestroyPossessionTransaction(
            user: $request->user(),
            transaction: $possessionTransaction,
        )->execute();

        return redirect()
            ->route('possessions.transactions.index', [$vault, $possession])
            ->with('status', __('The transaction is deleted.'));
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(PossessionTransactionTypeEnum::class)],
            'occurred_on' => ['nullable', 'date'],
            'counterparty' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'fees' => ['nullable', 'numeric', 'min:0'],
            'shipping' => ['nullable', 'numeric', 'min:0'],
            'entered_total' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', Rule::enum(CurrencyEnum::class)],
            'reference' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
