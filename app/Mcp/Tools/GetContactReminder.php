<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\ReminderResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one reminder about a contact: what it says, where its date comes from, how often it comes back and its next date.')]
#[IsReadOnly]
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

        $contact = $this->contact($this->vault($request, $validated['vault']), $validated['contact']);

        return Response::json(new ReminderResource($this->reminder($contact, $validated['reminder']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the contact.')->required(),
            'contact' => $schema->string()->description('The address of the contact.')->required(),
            'reminder' => $schema->string()->description('The identifier of the reminder, as list-contact-reminders gives it.')->required(),
        ];
    }
}
