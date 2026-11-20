<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one reminder about a contact: what it says, where its date comes from, how often it comes back and its next date.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class GetContactReminder extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.reminders.show';

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
        ];
    }
}
