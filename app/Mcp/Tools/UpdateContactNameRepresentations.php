<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateContactNameRepresentations as UpdateContactNameRepresentationsAction;
use App\Http\Resources\ContactResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Replace every way the name of a contact is written, and choose the one it is shown under. A way left out of the list is deleted. Nothing is transliterated for you. The change is written to the account journal.')]
class UpdateContactNameRepresentations extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.nameRepresentations.update';

    public function handle(Request $request): Response
    {
        $vault = $this->vault($request, (string) $request->get('vault'));
        $contact = $this->contact($vault, (string) $request->get('contact'));

        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            ...UpdateContactNameRepresentationsAction::rules('name_representations', $contact->nameRepresentations()->pluck('uuid')->all()),
            'nickname' => ['nullable', 'string', 'max:255'],
            'maiden_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            new UpdateContactNameRepresentationsAction(
                user: $this->user($request),
                contact: $contact,
                nameRepresentations: array_values($validated['name_representations']),
                display: isset($validated['display']) ? (int) $validated['display'] : null,
                nickname: array_key_exists('nickname', $validated) ? $validated['nickname'] : $contact->nickname,
                maidenName: array_key_exists('maiden_name', $validated) ? $validated['maiden_name'] : $contact->maiden_name,
            )->execute();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['name_representations' => $e->getMessage()]);
        }

        return Response::json(new ContactResource($this->contact($vault, $contact->uuid))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the contact.')->required(),
            'contact' => $schema->string()->description('The address of the contact.')->required(),
            'name_representations' => $schema->array()->items($schema->object())->description('Every way the name is written, in order. Each: id (to keep an existing one, as list-contact-name-representations gives it), kind (written, transliteration or phonetic), language (a BCP 47 tag such as ko), script (an ISO 15924 code such as Hang or Latn), phonetic_system (required for phonetic, such as ipa or piny), formatted_name, prefix, given_name, additional_name, family_name, suffix, name_order (given_first or family_first). Each needs a formatted_name or at least one part. A phonetic one holds parts only, never a formatted_name, and cannot be the one displayed.')->required(),
            'display' => $schema->integer()->description('The position in the list, from 0, of the way the contact is shown under. Required unless the list is empty.'),
            'nickname' => $schema->string()->description('What they are actually called. Leave it out to keep what is there.'),
            'maiden_name' => $schema->string()->description('Their name before marriage. Leave it out to keep what is there.'),
        ];
    }
}
