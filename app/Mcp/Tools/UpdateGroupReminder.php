<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\ReminderCadenceEnum;
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

#[Description('Change what a reminder about a group says and when it comes back. Dates after today that nobody answered are replaced. Answers already given never change. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld]
class UpdateGroupReminder extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.reminders.update';

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

        $vault = $this->vault($request, $validated['vault']);
        $group = $this->group($vault, $validated['group']);
        $reminder = $this->groupReminder($group, $validated['reminder']);

        return $this->answerThroughApi($request, $vault->uuid, $group->uuid, $reminder->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'reminder' => $schema->string()->description('The id of the reminder, as list-group-reminders gives it.')->required(),
            'name' => $schema->string()->description('What the reminder says.'),
            'starts_on' => $schema->string()->description('A new first date, as YYYY-MM-DD.'),
            'cadence' => $schema->string()->enum(ReminderCadenceEnum::values())->description('How often the reminder comes back.'),
            'interval' => $schema->integer()->description('The number of cadence units between two dates, from 1 to 99: cadence weekly with interval 2 means every two weeks.'),
            'time' => $schema->string()->description('The local time the reminder is notified at, as HH:MM.'),
            'timezone' => $schema->string()->description('The time zone the time is read in.'),
        ];
    }
}
