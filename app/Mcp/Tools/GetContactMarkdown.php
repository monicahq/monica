<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\ExportContactMarkdown;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Return everything recorded about one contact as a single Markdown document: names, fields, relationships, life events, groups, reminders and loans. The values of sensitive fields are given only to a key holding the sensitive fields scope. The export is written to the account log.')]
#[IsReadOnly]
class GetContactMarkdown extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.markdown.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);

        return Response::text(new ExportContactMarkdown(
            user: $this->user($request),
            contact: $contact,
            reachesSensitiveFields: $this->reachesSensitiveFields($request),
        )->execute());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()
                ->description('The id of the vault, as list-vaults gives it.')
                ->required(),
            'contact' => $schema->string()
                ->description('The id of the contact, as list-contacts gives it.')
                ->required(),
        ];
    }
}
