<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\AccountImportOutcomeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\AccountImportItemResource;
use App\Models\AccountImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountImportReportController extends Controller
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

        $items = $import->items()
            ->where('outcome', AccountImportOutcomeEnum::LeftOut)
            ->select(['id', 'kind', 'source_uuid', 'reason'])
            ->paginate($perPage);

        return response()->json([
            'data' => AccountImportItemResource::collection($items->items())->resolve(),
            'meta' => [
                'page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }
}
