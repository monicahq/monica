<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\EmptyVaultTrash;
use App\Enums\TrashItemKindEnum;
use App\Helpers\TrashItem;
use App\Helpers\VaultTrash;
use App\Http\Controllers\Controller;
use App\Http\Resources\TrashItemResource;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class TrashController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        $validated = $request->validate([
            'kind' => ['nullable', 'string', Rule::enum(TrashItemKindEnum::class)],
        ]);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $items = new VaultTrash(vault: $vault)->paginate(
            kind: isset($validated['kind']) ? TrashItemKindEnum::from($validated['kind']) : null,
            perPage: $perPage,
            page: max(1, $request->integer('page', 1)),
        );

        return response()->json([
            'data' => array_map(
                fn (TrashItem $item): array => new TrashItemResource($item)->resolve($request),
                $items->items(),
            ),
            'meta' => [
                'page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function destroy(Request $request, string $vault): Response
    {
        new EmptyVaultTrash(
            user: $request->user(),
            vault: $this->vault($request, $vault),
        )->execute();

        return response()->noContent(204);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }
}
