<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateContact as CreateContactAction;
use App\Enums\NameOrderEnum;
use App\Exceptions\VaultHasNoContactDefault;
use App\Http\Resources\ContactResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Record a new contact in a vault. The change is written to the account journal.')]
class CreateContact extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'name_order' => ['nullable', Rule::enum(NameOrderEnum::class)],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        try {
            $contact = new CreateContactAction(
                user: $this->user($request),
                vault: $vault,
                structureVersion: null,
                firstName: $validated['first_name'],
                middleName: $validated['middle_name'] ?? null,
                lastName: $validated['last_name'] ?? null,
                nickname: $validated['nickname'] ?? null,
                nameOrder: isset($validated['name_order']) ? NameOrderEnum::from($validated['name_order']) : null,
            )->execute();
        } catch (AuthorizationException|VaultHasNoContactDefault $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new ContactResource($this->contact($vault, $contact->uuid))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()
                ->description('The address of the vault to record them in.')
                ->required(),
            'first_name' => $schema->string()
                ->description('Their given name, the one part a contact cannot go without.')
                ->required(),
            'last_name' => $schema->string()->description('Their family name.'),
            'middle_name' => $schema->string()->description('Their middle name.'),
            'nickname' => $schema->string()->description('What they are actually called.'),
            'name_order' => $schema->string()
                ->description('Which part of the name reads first: '.implode(', ', array_column(NameOrderEnum::cases(), 'value')).'.'),
        ];
    }
}
