<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContactDuplicateCandidateResource;
use App\Models\ContactDuplicateCandidate;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactDuplicateController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $candidates = ContactDuplicateCandidate::query()
            ->standing()
            ->where('vault_id', $vault->id)
            ->with(['contact', 'otherContact'])
            ->orderByDesc('score')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => ContactDuplicateCandidateResource::collection($candidates->items())->resolve(),
            'meta' => [
                'page' => $candidates->currentPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total(),
            ],
        ]);
    }

    private function vault(Request $request, string $uuid): Vault
    {
        return $request->user()->account->vaults()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
