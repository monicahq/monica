<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateNotificationChannel;
use App\Exceptions\ChannelIsNotValid;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationChannelResource;
use App\Models\NotificationChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NotificationChannelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $channels = NotificationChannel::query()
            ->where('account_id', $request->user()->account_id)
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => NotificationChannelResource::collection($channels)->resolve(),
        ]);
    }

    public function show(Request $request, NotificationChannel $channel): JsonResponse
    {
        return new NotificationChannelResource($channel)->response()->setStatusCode(200);
    }

    public function update(Request $request, NotificationChannel $channel): JsonResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'destination' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            new UpdateNotificationChannel(
                user: $request->user(),
                channel: $channel,
                isEnabled: (bool) $validated['is_enabled'],
                destination: $validated['destination'] ?? null,
            )->execute();
        } catch (ChannelIsNotValid $e) {
            throw ValidationException::withMessages(['is_enabled' => $e->getMessage()]);
        }

        return new NotificationChannelResource($channel->refresh())->response()->setStatusCode(200);
    }
}
