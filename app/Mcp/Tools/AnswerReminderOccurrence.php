<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\AnswerReminderOccurrence as AnswerReminderOccurrenceAction;
use App\Enums\ReminderAnswerEnum;
use App\Http\Resources\ReminderOccurrenceResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Record, on the user\'s word, whether they remembered one reminder date or forgot it. remembered is accepted from the date\'s day, forgotten from the day after. The answer is final and cannot be changed. Only record what the user said. The change is written to the account journal.')]
class AnswerReminderOccurrence extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.reminderOccurrences.answer.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'occurrence' => ['required', 'string'],
            'answer' => ['required', 'string', Rule::in(ReminderAnswerEnum::values())],
        ]);

        $contact = $this->contact($this->vault($request, $validated['vault']), $validated['contact']);

        try {
            $occurrence = new AnswerReminderOccurrenceAction(
                user: $this->user($request),
                occurrence: $this->reminderOccurrence($contact, $validated['occurrence']),
                answer: ReminderAnswerEnum::from($validated['answer']),
            )->execute();
        } catch (AuthorizationException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new ReminderOccurrenceResource($occurrence->load(['vault', 'reminder.remindable']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the contact.')->required(),
            'contact' => $schema->string()->description('The address of the contact.')->required(),
            'occurrence' => $schema->string()->description('The identifier of the date, as list-contact-reminder-occurrences gives it.')->required(),
            'answer' => $schema->string()->description('remembered or forgotten.')->required(),
        ];
    }
}
