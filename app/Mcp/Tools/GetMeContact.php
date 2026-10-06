<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\ContactResource;
use App\Jobs\RecordContactVisit;
use App\Models\Contact;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Resolve the contact that represents the account holder in a vault.')]
#[IsReadOnly]
class GetMeContact extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.me';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        $contact = Contact::query()
            ->where('vault_id', $vault->id)
            ->where('is_me', true)
            ->with(['vault', 'structureVersion.structure.owner', 'avatar'])
            ->first();

        if ($contact === null) {
            throw $this->doesNotExist(__('That vault holds no contact standing for you.'));
        }

        RecordContactVisit::dispatch(contact: $contact)->onQueue('low');

        return Response::json(new ContactResource($contact)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()
                ->description('The address of the vault whose Me contact you want.')
                ->required(),
        ];
    }
}
