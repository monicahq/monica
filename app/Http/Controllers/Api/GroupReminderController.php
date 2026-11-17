<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateManualReminder;
use App\Actions\DestroyReminder;
use App\Actions\UpdateReminder;
use App\Enums\ReminderCadenceEnum;
use App\Exceptions\ReminderIsNotValid;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReminderResource;
use App\Models\Group;
use App\Models\Reminder;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GroupReminderController extends Controller
{
    public function index(Request $request, string $vault, string $group): JsonResponse
    {
        $group = $this->group($this->vault($request, $vault), $group);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $reminders = $group->reminders()
            ->with(['vault', 'field', 'fieldValue', 'remindable'])
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => ReminderResource::collection($reminders->items())->resolve(),
            'meta' => [
                'page' => $reminders->currentPage(),
                'per_page' => $reminders->perPage(),
                'total' => $reminders->total(),
            ],
        ]);
    }

    public function create(Request $request, string $vault, string $group): JsonResponse
    {
        $group = $this->group($this->vault($request, $vault), $group);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'cadence' => ['required', Rule::in(ReminderCadenceEnum::values())],
            'interval' => ['nullable', 'integer', 'min:1', 'max:'.Reminder::MAXIMUM_INTERVAL],
            'time' => ['required', 'date_format:H:i'],
            'timezone' => ['nullable', 'string', 'timezone'],
        ]);

        try {
            $reminder = new CreateManualReminder(
                user: $request->user(),
                record: $group,
                name: $validated['name'],
                startsOn: $validated['starts_on'],
                cadence: ReminderCadenceEnum::from($validated['cadence']),
                timeOfDay: $validated['time'],
                frequencyInterval: (int) ($validated['interval'] ?? 1),
                timezone: $validated['timezone'] ?? null,
            )->execute();
        } catch (ReminderIsNotValid $e) {
            throw ValidationException::withMessages([$e->field ?? 'starts_on' => $e->getMessage()]);
        }

        return new ReminderResource($reminder->load(['vault', 'remindable']))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $vault, string $group, string $reminder): JsonResponse
    {
        $group = $this->group($this->vault($request, $vault), $group);

        return new ReminderResource($this->reminder($group, $reminder))->response()->setStatusCode(200);
    }

    public function update(Request $request, string $vault, string $group, string $reminder): JsonResponse
    {
        $group = $this->group($this->vault($request, $vault), $group);
        $reminder = $this->reminder($group, $reminder);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'cadence' => ['sometimes', 'required', Rule::in(ReminderCadenceEnum::values())],
            'interval' => ['nullable', 'integer', 'min:1', 'max:'.Reminder::MAXIMUM_INTERVAL],
            'time' => ['sometimes', 'required', 'date_format:H:i'],
            'timezone' => ['nullable', 'string', 'timezone'],
        ]);

        try {
            new UpdateReminder(
                user: $request->user(),
                reminder: $reminder,
                cadence: isset($validated['cadence']) ? ReminderCadenceEnum::from($validated['cadence']) : $reminder->cadence,
                timeOfDay: $validated['time'] ?? $reminder->time_of_day,
                name: $validated['name'] ?? $reminder->name,
                frequencyInterval: (int) ($validated['interval'] ?? $reminder->frequency_interval),
                timezone: $validated['timezone'] ?? null,
                startsOn: $validated['starts_on'] ?? null,
            )->execute();
        } catch (ReminderIsNotValid $e) {
            throw ValidationException::withMessages([$e->field ?? 'starts_on' => $e->getMessage()]);
        }

        return new ReminderResource($this->reminder($group, $reminder->uuid))->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $vault, string $group, string $reminder): Response
    {
        $group = $this->group($this->vault($request, $vault), $group);

        new DestroyReminder(user: $request->user(), reminder: $this->reminder($group, $reminder))->execute();

        return response()->noContent(204);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function group(Vault $vault, string $address): Group
    {
        return Group::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $address)
            ->firstOrFail();
    }

    private function reminder(Group $group, string $uuid): Reminder
    {
        return $group->reminders()
            ->where('uuid', $uuid)
            ->with(['vault', 'field', 'fieldValue', 'remindable'])
            ->firstOrFail();
    }
}
