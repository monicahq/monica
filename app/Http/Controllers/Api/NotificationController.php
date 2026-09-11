<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $notifications = $this->scope($request)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => NotificationResource::collection($notifications->items())->resolve(),
            'meta' => [
                'page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function show(Request $request, Notification $notification): JsonResponse
    {
        return new NotificationResource($notification)->response()->setStatusCode(200);
    }

    /** @return Builder<Notification> */
    private function scope(Request $request): Builder
    {
        $query = Notification::query()
            ->where('account_id', $request->user()->account_id)
            ->where('user_id', $request->user()->id);

        $read = $request->query('read');

        if ($read === 'true') {
            return $query->whereNotNull('read_at');
        }

        if ($read === 'false') {
            return $query->whereNull('read_at');
        }

        return $query;
    }
}
