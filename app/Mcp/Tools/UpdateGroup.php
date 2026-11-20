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

#[Description('Change the name, the description or the dates of a group. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdateGroup extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.update';

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
            'name' => $schema->string()->description('A new name.'),
            'description' => $schema->string()->nullable()->description('Anything to say about the group.'),
            'started_on' => $schema->string()->nullable()->description('When the group came together, as YYYY-MM-DD.'),
            'started_precision' => $schema->string()->enum(['day', 'month', 'year', 'decade', 'unknown'])->description('How precisely the start is known.'),
            'ended_on' => $schema->string()->nullable()->description('When the group ended, as YYYY-MM-DD.'),
            'ended_precision' => $schema->string()->enum(['day', 'month', 'year', 'decade', 'unknown'])->description('How precisely the end is known.'),
        ];
    }
}
