<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmailSentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Every email we sent on behalf of an account, most recently sent first.
 *
 * The record belongs to the account rather than to the person, which is the
 * same choice the journal makes: an email sent to an address nobody has an
 * account for still left on the account's behalf, and scoping to the person
 * would hide it.
 *
 * There is nothing here but reading. The record is written as mail leaves and
 * updated only by delivery reports, so there is nothing for a caller to write.
 */
class EmailSentController extends Controller
{
    /**
     * The page is shaped by hand for the same reason as everywhere else in this
     * API: the reference promises `page`, `per_page` and `total`, and nothing
     * more.
     */
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
