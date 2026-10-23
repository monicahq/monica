<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyRelationship;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a relationship. It leaves both contacts at once. Neither person is deleted, including somebody who existed only through it. The change is written to the account journal.')]
#[IsDestructive]
class DeleteContactRelationship extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.relationships.destroy';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'relationship' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);

        new DestroyRelationship(
            user: $this->user($request),
            vault: $vault,
            relationship: $this->relationship($contact, $validated['relationship']),
        )->execute();

        return Response::text(__('The relationship is removed.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the contact.')->required(),
            'contact' => $schema->string()->description('The address of either person in the relationship.')->required(),
            'relationship' => $schema->string()->description('The identifier of the relationship, as list-contact-relationships gives it.')->required(),
        ];
    }
}
