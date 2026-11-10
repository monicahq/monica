<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\FlagState;
use App\Http\Resources\ReminderResource;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one reminder about a group: what it says, how often it comes back and its next date.')]
#[IsReadOnly]
class GetGroupReminder extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.reminders.show';

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

        return Response::json(new ReminderResource($this->groupReminder($group, $validated['reminder']))->resolve());
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
