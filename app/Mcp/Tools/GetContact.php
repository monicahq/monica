<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\ContactResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Look one contact up in a vault, by the address a listing gave for them.')]
class GetContact extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.show';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);

        return Response::json(new ContactResource($contact)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()
                ->description('The address of the vault holding the contact.')
                ->required(),
            'contact' => $schema->string()
                ->description('The address of the contact, as list-contacts gives it.')
                ->required(),
        ];
    }
}
