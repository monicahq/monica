<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\AnswerReminderOccurrence;
use App\Enums\ReminderAnswerEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReminderOccurrenceResource;
use App\Models\Contact;
use App\Models\ReminderOccurrence;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReminderOccurrenceAnswerController extends Controller
{
    public function update(Request $request, string $vault, string $contact, string $occurrence): JsonResponse
    {
        $contact = $this->contact($this->vault($request, $vault), $contact);
        $occurrence = $this->occurrence($contact, $occurrence);

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

    private function contact(Vault $vault, string $address): Contact
    {
        return Contact::query()
            ->where('vault_id', $vault->id)
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function occurrence(Contact $contact, string $uuid): ReminderOccurrence
    {
        return ReminderOccurrence::query()
            ->where('account_id', $contact->account_id)
            ->where('uuid', $uuid)
            ->whereIn('reminder_id', $contact->reminders()->select('id'))
            ->firstOrFail();
    }
}
