<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Possessions;

use App\Actions\AttachPossessionTransactionDocument;
use App\Actions\DetachPossessionTransactionDocument;
use App\Exceptions\NotEnoughStorageLeft;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionTransactionResource;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class PossessionTransactionDocumentController extends Controller
{
    public function create(Request $request, string $vault, string $possession, string $transaction): JsonResponse
    {
        $record = $this->transaction($this->possession($this->vault($request, $vault), $possession), $transaction);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:'.(int) config('monica.max_file_size_in_kilobytes')],
        ]);

        try {
            new AttachPossessionTransactionDocument(
                user: $request->user(),
                transaction: $record,
                upload: $validated['file'],
            )->execute();
        } catch (NotEnoughStorageLeft $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        return new PossessionTransactionResource($record->refresh())->response()->setStatusCode(201);
    }

    public function destroy(Request $request, string $vault, string $possession, string $transaction, string $file): Response
    {
        $record = $this->transaction($this->possession($this->vault($request, $vault), $possession), $transaction);

        new DetachPossessionTransactionDocument(
            user: $request->user(),
            transaction: $record,
            file: $record->documents()->where('files.uuid', $file)->firstOrFail(),
        )->execute();

        return response()->noContent(204);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function possession(Vault $vault, string $uuid): Possession
    {
        return Possession::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function transaction(Possession $possession, string $uuid): PossessionTransaction
    {
        return PossessionTransaction::query()
            ->where('possession_id', $possession->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
