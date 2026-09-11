<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\SendChannelTestMessage;
use App\Exceptions\ChannelIsNotValid;
use App\Http\Controllers\Controller;
use App\Models\NotificationChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChannelTestController extends Controller
{
    public function create(Request $request, NotificationChannel $channel): JsonResponse
    {
        try {
            $outcome = new SendChannelTestMessage(
                user: $request->user(),
                channel: $channel,
            )->execute();
        } catch (ChannelIsNotValid $e) {
            throw ValidationException::withMessages(['channel' => $e->getMessage()]);
        }

        return response()->json(['data' => $outcome], $outcome['sent'] ? 201 : 422);
    }
}
