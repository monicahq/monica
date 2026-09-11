<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\UpdateNotificationPreference;
use App\Enums\NotificationDeliveryModeEnum;
use App\Exceptions\NotificationPreferenceIsNotValid;
use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->shape($this->preference($request))]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quiet_hours_are_enabled' => ['required', 'boolean'],
            'quiet_hours_start' => ['nullable', 'string', 'max:5'],
            'quiet_hours_end' => ['nullable', 'string', 'max:5'],
            'delivery_mode' => ['required', 'string', Rule::enum(NotificationDeliveryModeEnum::class)],
            'digest_time' => ['nullable', 'string', 'max:5'],
            'digest_weekdays' => ['nullable', 'array'],
            'digest_weekdays.*' => ['integer'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        $preference = $this->preference($request);

        try {
            new UpdateNotificationPreference(
                user: $request->user(),
                preference: $preference,
                quietHoursAreEnabled: (bool) $validated['quiet_hours_are_enabled'],
                quietHoursStart: $validated['quiet_hours_start'] ?? null,
                quietHoursEnd: $validated['quiet_hours_end'] ?? null,
                deliveryMode: NotificationDeliveryModeEnum::from($validated['delivery_mode']),
                digestTime: $validated['digest_time'] ?? null,
                digestWeekdays: $validated['digest_weekdays'] ?? null,
                timezone: $validated['timezone'] ?? null,
            )->execute();
        } catch (NotificationPreferenceIsNotValid $e) {
            throw ValidationException::withMessages(['delivery_mode' => $e->getMessage()]);
        }

        return response()->json(['data' => $this->shape($preference->refresh())]);
    }

    private function preference(Request $request): NotificationPreference
    {
        return NotificationPreference::query()
            ->where('account_id', $request->user()->account_id)
            ->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function shape(NotificationPreference $preference): array
    {
        return [
            'type' => 'notification_preference',
            'id' => (string) $preference->id,
            'attributes' => [
                'quiet_hours_are_enabled' => $preference->quiet_hours_are_enabled,
                'quiet_hours_start' => $preference->quiet_hours_start,
                'quiet_hours_end' => $preference->quiet_hours_end,
                'delivery_mode' => $preference->delivery_mode->value,
                'digest_time' => $preference->digest_time,
                'digest_weekdays' => $preference->digest_weekdays,
                'timezone' => $preference->timezone,
            ],
            'links' => [
                'self' => route('api.v1.notificationPreferences.show'),
            ],
        ];
    }
}
