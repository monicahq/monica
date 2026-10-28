<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateAccountExport;
use App\Enums\ApiKeyScopeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\AccountExportResource;
use App\Models\AccountExport;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AccountExportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $exports = AccountExport::query()
            ->where('account_id', $request->user()->account_id)
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => AccountExportResource::collection($exports->items())->resolve(),
            'meta' => [
                'page' => $exports->currentPage(),
                'per_page' => $exports->perPage(),
                'total' => $exports->total(),
            ],
        ]);
    }

    public function show(Request $request, string $accountExport): JsonResponse
    {
        $export = AccountExport::query()
            ->where('account_id', $request->user()->account_id)
            ->where('uuid', $accountExport)
            ->firstOrFail();

        return new AccountExportResource($export)->response()->setStatusCode(200);
    }

    public function create(Request $request): JsonResponse
    {
        abort_unless(
            $this->reachesSensitiveFields($request->user()),
            403,
            __('An export holds every sensitive value, so it needs an API key with the sensitive fields scope.'),
        );

        $validated = $request->validate([
            'vaults' => ['nullable', 'array', 'min:1'],
            'vaults.*' => ['required', 'string', 'max:255'],
        ]);

        $vaultIds = null;

        foreach ($validated['vaults'] ?? [] as $address) {
            $vaultIds[] = $request->user()->account->vaults()->addressedBy($address)->firstOrFail()->id;
        }

        try {
            $export = new CreateAccountExport(
                user: $request->user(),
                vaultIds: $vaultIds,
            )->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['export' => $e->getMessage()]);
        }

        return new AccountExportResource($export)->response()->setStatusCode(202);
    }

    private function reachesSensitiveFields(User $user): bool
    {
        $token = $user->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        return in_array(ApiKeyScopeEnum::SensitiveFields->value, $token->abilities ?? [], true);
    }
}
