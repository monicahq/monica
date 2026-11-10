<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\DestroyReminder;
use App\Helpers\FlagState;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Stop a reminder about a group for good. There is no pause. Dates still waiting for an answer are dropped; answers already given are kept. The change is written to the account journal.')]
#[IsDestructive]
class DeleteGroupReminder extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.reminders.destroy';

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
            'reminder' => ['required', 'string'],
        ]);

        $group = $this->group($this->vault($request, $validated['vault']), $validated['group']);

        new DestroyReminder(
            user: $this->user($request),
            reminder: $this->groupReminder($group, $validated['reminder']),
        )->execute();

        return Response::text(__('The reminder is deleted.'));
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the group.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'reminder' => $schema->string()->description('The identifier of the reminder, as list-group-reminders gives it.')->required(),
        ];
    }
}
