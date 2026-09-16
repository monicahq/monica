<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\FavouriteContact;
use App\Actions\UnfavouriteContact;
use App\Http\Resources\ContactResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Mark a contact as a favourite, or stop marking them one.')]
class SetContactFavourite extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.favourite.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'favourite' => ['required', 'boolean'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $contact = $this->contact($vault, $validated['contact']);

        if ($validated['favourite']) {
            new FavouriteContact(user: $this->user($request), contact: $contact)->execute();
        }

        if (! $validated['favourite']) {
            new UnfavouriteContact(user: $this->user($request), contact: $contact)->execute();
        }

        return Response::json(new ContactResource($this->contact($vault, $contact->uuid))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the contact.')->required(),
            'contact' => $schema->string()->description('The address of the contact.')->required(),
            'favourite' => $schema->boolean()->description('True to mark them a favourite, false to stop.')->required(),
        ];
    }
}
