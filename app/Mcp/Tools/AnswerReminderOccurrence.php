<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\ReminderAnswerEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Record, on the user\'s word, whether they remembered one reminder date or forgot it. remembered is accepted from the date\'s day, forgotten from the day after. The answer is final and cannot be changed. Only record what the user said. The change is written to the account log.')]
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
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);
        $occurrence = $this->reminderOccurrence($contact, $validated['occurrence']);

        return $this->answerThroughApi($request, $vault->uuid, $contact->uuid, $occurrence->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'contact' => $schema->string()->description('The id of the contact, as list-contacts gives it.')->required(),
            'occurrence' => $schema->string()->description('The id of the date, as list-contact-reminder-occurrences gives it.')->required(),
            'answer' => $schema->string()->enum(ReminderAnswerEnum::values())->description('Whether the user remembered the date or forgot it.')->required(),
        ];
    }
}
