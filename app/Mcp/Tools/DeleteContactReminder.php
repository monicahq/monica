<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyReminder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Stop a reminder about a contact for good. There is no pause. Dates still waiting for an answer are dropped; answers already given are kept. The change is written to the account journal.')]
#[IsDestructive]
class DeleteContactReminder extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.reminders.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'reminder' => ['required', 'string'],
        ]);

        $contact = $this->contact($this->vault($request, $validated['vault']), $validated['contact']);

        new DestroyReminder(
            user: $this->user($request),
            reminder: $this->reminder($contact, $validated['reminder']),
        )->execute();

        return Response::text(__('The reminder is deleted.'));
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
