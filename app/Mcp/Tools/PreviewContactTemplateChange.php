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

#[Description('Say what changing a contact\'s template would do, without changing anything: the answers that would be set aside under Unused answers, those that would come back, and the reminders that would stop or run again. Call it before change-contact-template and tell the person what it says.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class PreviewContactTemplateChange extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.templatePreview.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'template' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);
        $this->template($request, $validated['template']);

        return $this->answerThroughApi($request, $vault->uuid, $contact->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'contact' => $schema->string()->description('The id of the contact, as list-contacts gives it.')->required(),
            'template' => $schema->string()->description('The id of the template to preview, as list-templates gives it.')->required(),
            'structure_version_id' => $schema->integer()->nullable()->description('A published version of that template, as list-template-versions gives it. Leave it out to preview what the template publishes now.'),
        ];
    }
}
