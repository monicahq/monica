<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\ConfirmChannelProofCode;
use App\Actions\SendChannelProofCode;
use App\Exceptions\ChannelIsNotValid;
use App\Exceptions\ProofCodeIsNotValid;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationChannelResource;
use App\Models\NotificationChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChannelProofController extends Controller
{
    public function create(Request $request, NotificationChannel $channel): JsonResponse
    {
        if (($destination = $request->input('destination')) !== null) {
            $channel->destination = $destination;
            $channel->save();
        }

        try {
            new SendChannelProofCode(
                user: $request->user(),
                channel: $channel,
            )->execute();
        } catch (ChannelIsNotValid $e) {
            throw ValidationException::withMessages(['channel' => $e->getMessage()]);
        }

        return new NotificationChannelResource($channel->refresh())->response()->setStatusCode(201);
    }

    public function update(Request $request, NotificationChannel $channel): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:16'],
        ]);

        try {
            new ConfirmChannelProofCode(
                user: $request->user(),
                channel: $channel,
                code: $validated['code'],
            )->execute();
        } catch (ProofCodeIsNotValid $e) {
            throw ValidationException::withMessages(['code' => $e->getMessage()]);
        }

        return new NotificationChannelResource($channel->refresh())->response()->setStatusCode(200);
    }
}
