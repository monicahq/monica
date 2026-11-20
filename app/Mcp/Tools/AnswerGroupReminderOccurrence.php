<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\ReminderAnswerEnum;
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

#[Description('Record, on the user\'s word, whether they remembered one date of a reminder about a group or forgot it. remembered is accepted from the date\'s day, forgotten from the day after. The answer is final and cannot be changed. Only record what the user said. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class AnswerGroupReminderOccurrence extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.reminderOccurrences.answer.update';

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
            'occurrence' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $group = $this->group($vault, $validated['group']);
        $occurrence = $this->groupReminderOccurrence($group, $validated['occurrence']);

        return $this->answerThroughApi($request, $vault->uuid, $group->uuid, $occurrence->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'occurrence' => $schema->string()->description('The id of the date, as list-group-reminder-occurrences gives it.')->required(),
            'answer' => $schema->string()->enum(ReminderAnswerEnum::values())->description('Whether the user remembered the date or forgot it.')->required(),
        ];
    }
}
