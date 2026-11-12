<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Possessions;

use App\Actions\AttachPossessionValuationDocument;
use App\Actions\DetachPossessionValuationDocument;
use App\Exceptions\NotEnoughStorageLeft;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionValuationResource;
use App\Models\Possession;
use App\Models\PossessionValuation;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class PossessionValuationDocumentController extends Controller
{
    public function create(Request $request, string $vault, string $possession, string $valuation): JsonResponse
    {
        $record = $this->valuation($this->possession($this->vault($request, $vault), $possession), $valuation);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:'.(int) config('monica.max_file_size_in_kilobytes')],
        ]);

        try {
            new AttachPossessionValuationDocument(
                user: $request->user(),
                valuation: $record,
                upload: $validated['file'],
            )->execute();
        } catch (NotEnoughStorageLeft $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        return new PossessionValuationResource($record->refresh())->response()->setStatusCode(201);
    }

    public function destroy(Request $request, string $vault, string $possession, string $valuation, string $file): Response
    {
        $record = $this->valuation($this->possession($this->vault($request, $vault), $possession), $valuation);

        new DetachPossessionValuationDocument(
            user: $request->user(),
            valuation: $record,
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

    private function valuation(Possession $possession, string $uuid): PossessionValuation
    {
        return PossessionValuation::query()
            ->where('possession_id', $possession->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
