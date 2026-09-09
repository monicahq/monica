<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $payments = $request->user()->account->payments()->paginate($perPage);

        return response()->json([
            'data' => PaymentResource::collection($payments->items())->resolve(),
            'meta' => [
                'page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function show(Request $request, string $payment): JsonResponse
    {
        $payment = $request->user()->account->payments()->where('stripe_id', $payment)->firstOrFail();

        return new PaymentResource($payment)->response()->setStatusCode(200);
    }
}
