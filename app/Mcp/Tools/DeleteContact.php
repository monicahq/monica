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

#[Description('Delete a contact, and keep everything they took part in. Nothing an assistant can call brings the contact back, so ask before calling it. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class DeleteContact extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);

        $answer = $this->answerThroughApi($request, $vault->uuid, $contact->uuid);

        if ($answer->isError()) {
            return $answer;
        }

        return Response::text(__('The contact is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'contact' => $schema->string()->description('The id of the contact, as list-contacts gives it.')->required(),
        ];
    }
}
