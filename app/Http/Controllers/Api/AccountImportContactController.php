<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountImportContactResource;
use App\Models\AccountImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountImportContactController extends Controller
{
    public function index(Request $request, string $accountImport): JsonResponse
    {
        $import = AccountImport::query()
            ->where('account_id', $request->user()->account_id)
            ->where('uuid', $accountImport)
            ->firstOrFail();

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));
        $search = $request->string('search')->trim()->value();

        $contacts = $import->items()
            ->reorder()
            ->where('kind', 'contact')
            ->when($search !== '', fn ($query) => $query->whereLike('name', "%{$search}%"))
            ->orderBy('name')
            ->orderBy('id')
            ->select(['id', 'source_uuid', 'name'])
            ->paginate($perPage);

        return response()->json([
            'data' => AccountImportContactResource::collection($contacts->items())->resolve(),
            'meta' => [
                'page' => $contacts->currentPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
            ],
        ]);
    }
}
