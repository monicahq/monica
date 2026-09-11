<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\MarkNotificationsAsRead;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationReadController extends Controller
{
    public function update(Request $request, Notification $notification): JsonResponse
    {
        new MarkNotificationsAsRead(
            user: $request->user(),
            notification: $notification,
        )->execute();

        return new NotificationResource($notification->refresh())->response()->setStatusCode(200);
    }

    public function updateAll(Request $request): JsonResponse
    {
        $marked = new MarkNotificationsAsRead(user: $request->user())->execute();

        return response()->json(['data' => ['marked' => $marked]]);
    }
}
