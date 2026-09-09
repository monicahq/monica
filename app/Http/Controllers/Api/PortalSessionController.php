<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PortalSessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $account = $request->user()->account;

        if ($account->stripe_id === null) {
            throw ValidationException::withMessages([
                'account' => __('Stripe does not know this account yet, because nothing has been paid from it.'),
            ]);
        }

        try {
            $url = $account->billingPortalUrl(route('settings.billing.index'));
        } catch (Exception $e) {
            abort(503, $e->getMessage());
        }

        return response()->json([
            'data' => [
                'type' => 'portal_session',
                'attributes' => [
                    'url' => $url,
                ],
            ],
        ], 201);
    }
}
