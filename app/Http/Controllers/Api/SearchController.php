<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\SearchRecords;
use App\Enums\ApiKeyScopeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\SearchResultResource;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class SearchController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:255'],
            'scope' => ['nullable', 'string', 'in:vault,account'],
            'group' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $withSensitive = $this->reachesSensitiveFields($request->user());

        $result = new SearchRecords(
            user: $request->user(),
            vault: $vault,
            query: $validated['query'],
            acrossAccount: ($validated['scope'] ?? 'vault') === 'account',
            group: $validated['group'] ?? null,
            withSensitive: $withSensitive,
            page: (int) ($validated['page'] ?? 1),
            perPage: $perPage,
        )->execute();

        if ($result['status'] === SearchRecords::UNAVAILABLE) {
            return response()->json(['message' => __('Search is unavailable right now')], 503);
        }

        if ($result['status'] === SearchRecords::TOO_SHORT) {
            return response()->json([
                'message' => __('Type at least two letters or digits to search.'),
                'errors' => ['query' => [__('Type at least two letters or digits to search.')]],
            ], 422);
        }

        return response()->json([
            'data' => array_map(fn (array $group): array => [
                'group' => $group['key'],
                'label' => $group['label'],
                'total' => $group['results']->total(),
                'next_page' => $group['results']->hasMorePages() ? $group['results']->currentPage() + 1 : null,
                'results' => collect($group['results']->items())->map(function (Contact|Entity|LifeEvent $record) use ($result, $withSensitive, $request): array {
                    $resource = SearchResultResource::make($record);
                    $resource->words = $result['words'];
                    $resource->withSensitive = $withSensitive;

                    return $resource->toArray($request);
                })->values()->all(),
            ], $result['groups']),
            'meta' => [
                'query' => $validated['query'],
                'scope' => $validated['scope'] ?? 'vault',
            ],
        ]);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
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
