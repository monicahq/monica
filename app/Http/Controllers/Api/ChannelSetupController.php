<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\RemoveChannelSetup;
use App\Enums\NotificationChannelEnum;
use App\Exceptions\ChannelIsNotValid;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationChannelResource;
use App\Models\NotificationChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChannelSetupController extends Controller
{
    public function destroy(Request $request, string $channel): JsonResponse
    {
        $name = NotificationChannelEnum::tryFrom($channel);

        abort_unless($name instanceof NotificationChannelEnum, 404);

        $row = NotificationChannel::query()
            ->where('account_id', $request->user()->account_id)
            ->where('channel', $name->value)
            ->first();

        abort_unless($row instanceof NotificationChannel, 404);

        try {
            new RemoveChannelSetup(
                user: $request->user(),
                channel: $row,
            )->execute();
        } catch (ChannelIsNotValid $e) {
            throw ValidationException::withMessages(['channel' => $e->getMessage()]);
        }

        return new NotificationChannelResource($row->refresh())->response()->setStatusCode(200);
    }
}
