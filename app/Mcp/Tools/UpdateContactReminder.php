<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\ReminderCadenceEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Change what a reminder about a contact says and when it comes back. Dates after today that nobody answered are replaced. Answers already given never change. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld]
class UpdateContactReminder extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.reminders.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'reminder' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);
        $reminder = $this->reminder($contact, $validated['reminder']);

        return $this->answerThroughApi($request, $vault->uuid, $contact->uuid, $reminder->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'contact' => $schema->string()->description('The id of the contact, as list-contacts gives it.')->required(),
            'reminder' => $schema->string()->description('The id of the reminder, as list-contact-reminders gives it.')->required(),
            'name' => $schema->string()->description('What the reminder says.'),
            'starts_on' => $schema->string()->description('A new first date, as YYYY-MM-DD, for a reminder written by hand only.'),
            'cadence' => $schema->string()->enum(ReminderCadenceEnum::values())->description('How often the reminder comes back.'),
            'interval' => $schema->integer()->description('The number of cadence units between two dates, from 1 to 99: cadence weekly with interval 2 means every two weeks.'),
            'time' => $schema->string()->description('The local time the reminder is notified at, as HH:MM.'),
            'timezone' => $schema->string()->description('The time zone the time is read in.'),
        ];
    }
}
