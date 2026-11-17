<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Replace every way the name of a contact is written, and choose the one it is shown under. A way left out of the list is deleted. Nothing is transliterated for you. The change is written to the account log.')]
class UpdateContactNameRepresentations extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.nameRepresentations.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);

        return $this->answerThroughApi($request, $vault->uuid, $contact->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'contact' => $schema->string()->description('The id of the contact, as list-contacts gives it.')->required(),
            'name_representations' => $schema->array()->items($schema->object())->description('Every way the name is written, in order. Each: id (to keep an existing one, as list-contact-name-representations gives it), kind (written, transliteration or phonetic), language (a BCP 47 tag such as ko), script (an ISO 15924 code such as Hang or Latn), phonetic_system (required for phonetic, such as ipa or piny), formatted_name, prefix, given_name, additional_name, family_name, suffix, name_order (given_first or family_first). Each needs a formatted_name or at least one part. A phonetic one holds parts only, never a formatted_name, and cannot be the one displayed.'),
            'display' => $schema->integer()->nullable()->description('The position in name_representations, from 0, of the way the contact is shown under. Required with name_representations, unless that list is empty.'),
            'nickname' => $schema->string()->nullable()->description('What they are actually called.'),
            'maiden_name' => $schema->string()->nullable()->description('Their name before marriage.'),
        ];
    }
}
