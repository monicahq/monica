<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmailSentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailSentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $emails = $request->user()->account->emailsSent()
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => EmailSentResource::collection($emails->items())->resolve(),
            'meta' => [
                'page' => $emails->currentPage(),
                'per_page' => $emails->perPage(),
                'total' => $emails->total(),
            ],
        ]);
    }

    public function show(Request $request, int $emailSent): JsonResponse
    {
        $email = $request->user()->account->emailsSent()->findOrFail($emailSent);

        return new EmailSentResource($email)->response()->setStatusCode(200);
    }
}
