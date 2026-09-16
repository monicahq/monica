<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateContactName as UpdateContactNameAction;
use App\Enums\NameOrderEnum;
use App\Http\Resources\ContactResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Correct the name of a contact. The change is written to the account journal.')]
class UpdateContactName extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.name.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'name_order' => ['nullable', Rule::enum(NameOrderEnum::class)],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);

        new UpdateContactNameAction(
            user: $this->user($request),
            contact: $contact,
            prefix: $contact->prefix,
            firstName: $validated['first_name'] ?? $contact->first_name,
            middleName: $validated['middle_name'] ?? $contact->middle_name,
            lastName: $validated['last_name'] ?? $contact->last_name,
            suffix: $contact->suffix,
            maidenName: $contact->maiden_name,
            nickname: $validated['nickname'] ?? $contact->nickname,
            nameOrder: isset($validated['name_order'])
                ? NameOrderEnum::from($validated['name_order'])
                : $contact->name_order,
        )->execute();

        return Response::json(new ContactResource($this->contact($vault, $contact->uuid))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the contact.')->required(),
            'contact' => $schema->string()->description('The address of the contact to correct.')->required(),
            'first_name' => $schema->string()->description('Their given name. Leave it out to keep what is there.'),
            'last_name' => $schema->string()->description('Their family name. Leave it out to keep what is there.'),
            'middle_name' => $schema->string()->description('Their middle name. Leave it out to keep what is there.'),
            'nickname' => $schema->string()->description('What they are actually called. Leave it out to keep what is there.'),
            'name_order' => $schema->string()->description('Which part of the name reads first.'),
        ];
    }
}
