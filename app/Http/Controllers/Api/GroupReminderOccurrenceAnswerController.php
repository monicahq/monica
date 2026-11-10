<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\AnswerReminderOccurrence;
use App\Enums\ReminderAnswerEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReminderOccurrenceResource;
use App\Models\Group;
use App\Models\ReminderOccurrence;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GroupReminderOccurrenceAnswerController extends Controller
{
    public function update(Request $request, string $vault, string $group, string $occurrence): JsonResponse
    {
        $group = $this->group($this->vault($request, $vault), $group);
        $occurrence = $this->occurrence($group, $occurrence);

        $validated = $request->validate([
            'answer' => ['required', 'string', Rule::in(ReminderAnswerEnum::values())],
        ]);

        try {
            $occurrence = new AnswerReminderOccurrence(
                user: $request->user(),
                occurrence: $occurrence,
                answer: ReminderAnswerEnum::from($validated['answer']),
            )->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['answer' => $e->getMessage()]);
        }

        return new ReminderOccurrenceResource($occurrence->load(['vault', 'reminder.remindable']))->response()->setStatusCode(200);
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

    private function occurrence(Group $group, string $uuid): ReminderOccurrence
    {
        return ReminderOccurrence::query()
            ->where('account_id', $group->account_id)
            ->where('uuid', $uuid)
            ->whereIn('reminder_id', $group->reminders()->select('id'))
            ->firstOrFail();
    }
}
