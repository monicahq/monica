<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CancelAccountImport;
use App\Actions\CreateAccountImport;
use App\Actions\StartAccountImport;
use App\Exceptions\ImportExceedsALimit;
use App\Http\Controllers\Controller;
use App\Http\Resources\AccountImportResource;
use App\Models\AccountImport;
use App\ViewModels\Settings\Account\Transfer\AccountTransferViewModel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AccountImportController extends Controller
{
    public function show(Request $request, string $accountImport): JsonResponse
    {
        $import = $this->accountImport($request, $accountImport);

        return new AccountImportResource($import)->response()->setStatusCode(200);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:'.AccountTransferViewModel::MAXIMUM_UPLOAD_IN_KILOBYTES],
        ]);

        try {
            $import = new CreateAccountImport(
                user: $request->user(),
                file: $validated['file'],
            )->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        return new AccountImportResource($import)->response()->setStatusCode(202);
    }

    public function update(Request $request, string $accountImport): JsonResponse
    {
        $import = $this->accountImport($request, $accountImport);

        $validated = $request->validate([
            'vault' => ['nullable', 'string'],
            'vault_name' => ['nullable', Rule::requiredIf(! $import->holdsSeveralVaults() && $request->string('vault')->isEmpty()), 'string', 'max:255'],
            'me_contact' => ['nullable', 'string', 'max:64'],
        ]);

        $vault = isset($validated['vault'])
            ? $request->user()->account->vaults()->addressedBy($validated['vault'])->firstOrFail()
            : null;

        try {
            new StartAccountImport(
                user: $request->user(),
                import: $import,
                vault: $vault,
                vaultName: $validated['vault_name'] ?? null,
                meSourceUuid: $validated['me_contact'] ?? null,
            )->execute();
        } catch (ImportExceedsALimit|InvalidArgumentException|AuthorizationException $e) {
            throw ValidationException::withMessages(['import' => $e->getMessage()]);
        }

        return new AccountImportResource($import)->response()->setStatusCode(202);
    }

    public function destroy(Request $request, string $accountImport): JsonResponse|Response
    {
        $import = $this->accountImport($request, $accountImport);

        try {
            new CancelAccountImport(
                user: $request->user(),
                import: $import,
            )->execute();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['import' => $e->getMessage()]);
        }

        if (! $import->exists) {
            return response()->noContent(204);
        }

        return new AccountImportResource($import)->response()->setStatusCode(202);
    }

    private function accountImport(Request $request, string $uuid): AccountImport
    {
        return AccountImport::query()
            ->where('account_id', $request->user()->account_id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
