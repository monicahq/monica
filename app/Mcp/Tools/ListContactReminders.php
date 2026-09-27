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

#[Description('List the reminders about one contact: those written by hand with a date of their own, and those reading a date on the contact, such as a birthday.')]
#[IsReadOnly]
class ListContactReminders extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.reminders';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
        ]);

        $contact = $this->contact($this->vault($request, $validated['vault']), $validated['contact']);

        $reminders = $contact->reminders()
            ->with(['vault', 'field', 'fieldValue', 'remindable'])
            ->orderBy('id')
            ->get();

        return Response::json(ReminderResource::collection($reminders)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the contact.')->required(),
            'contact' => $schema->string()->description('The address of the contact.')->required(),
        ];
    }
}
