<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\AttachPossessionTransactionDocument;
use App\Actions\DetachPossessionTransactionDocument;
use App\Exceptions\NotEnoughStorageLeft;
use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\Vault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PossessionTransactionDocumentController extends Controller
{
    public function create(Request $request, Vault $vault, Possession $possession, PossessionTransaction $possessionTransaction): RedirectResponse
    {
        abort_unless($possessionTransaction->possession_id === $possession->id, 404);

        $validated = $request->validate([
            'document' => ['required', 'file', 'max:'.(int) config('monica.max_file_size_in_kilobytes')],
        ]);

        try {
            new AttachPossessionTransactionDocument(
                user: $request->user(),
                transaction: $possessionTransaction,
                upload: $validated['document'],
            )->execute();
        } catch (NotEnoughStorageLeft $e) {
            return back()->withErrors(["document.{$possessionTransaction->uuid}" => $e->getMessage()]);
        }

        return redirect()
            ->route('possessions.transactions.index', [$vault, $possession])
            ->with('status', __('The document is attached.'));
    }

    public function destroy(Request $request, Vault $vault, Possession $possession, PossessionTransaction $possessionTransaction, File $file): RedirectResponse
    {
        abort_unless($possessionTransaction->possession_id === $possession->id, 404);

        new DetachPossessionTransactionDocument(
            user: $request->user(),
            transaction: $possessionTransaction,
            file: $file,
        )->execute();

        return redirect()
            ->route('possessions.transactions.index', [$vault, $possession])
            ->with('status', __('The document is removed.'));
    }
}
