<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ApiKeyScopeEnum;
use App\Helpers\ReminderDates;
use App\Helpers\VaultReminderDates;
use App\Http\Controllers\Controller;
use App\Http\Resources\VaultReminderDateResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class VaultReminderOccurrenceController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $vault = $request->user()->account->vaults()
            ->addressedBy($vault)
            ->firstOrFail();

        $validated = $request->validate([
            'category' => ['nullable', 'string', Rule::in(ReminderDates::categories())],
            'kind' => ['nullable', 'string', Rule::in(VaultReminderDates::kinds())],
            'days' => ['nullable', 'integer', 'min:1', 'max:'.VaultReminderDates::MaximumDays],
        ]);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $paginated = VaultReminderDates::page(
            user: $request->user(),
            vault: $vault,
            category: $validated['category'] ?? null,
            kind: $validated['kind'] ?? VaultReminderDates::All,
            days: (int) ($validated['days'] ?? VaultReminderDates::DefaultDays),
            reachesSensitiveFields: $this->reachesSensitiveFields($request->user()),
            perPage: $perPage,
            page: max(1, (int) $request->query('page', '1')),
        );

        return response()->json([
            'data' => VaultReminderDateResource::collection($paginated->items())->resolve(),
            'meta' => [
                'page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
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
