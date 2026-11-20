<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\FlagState;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Write a note about a group as a whole, such as where they usually meet or who has the spare key. It stays on the group and never reaches the page of a member. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreateGroupNote extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.notes.create';

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof User && FlagState::isOn('groups', $user->account);
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'group' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $group = $this->group($vault, $validated['group']);

        return $this->answerThroughApi($request, $vault->uuid, $group->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'body' => $schema->string()->description('What to write down, as plain text.')->required(),
        ];
    }
}
