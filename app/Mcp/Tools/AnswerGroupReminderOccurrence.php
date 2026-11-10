<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\AnswerReminderOccurrence as AnswerReminderOccurrenceAction;
use App\Enums\ReminderAnswerEnum;
use App\Helpers\FlagState;
use App\Http\Resources\ReminderOccurrenceResource;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Record, on the user\'s word, whether they remembered one date of a reminder about a group or forgot it. remembered is accepted from the date\'s day, forgotten from the day after. The answer is final and cannot be changed. Only record what the user said. The change is written to the account journal.')]
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
            'answer' => ['required', 'string', Rule::in(ReminderAnswerEnum::values())],
        ]);

        $group = $this->group($this->vault($request, $validated['vault']), $validated['group']);

        try {
            $occurrence = new AnswerReminderOccurrenceAction(
                user: $this->user($request),
                occurrence: $this->groupReminderOccurrence($group, $validated['occurrence']),
                answer: ReminderAnswerEnum::from($validated['answer']),
            )->execute();
        } catch (AuthorizationException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new ReminderOccurrenceResource($occurrence->load(['vault', 'reminder.remindable']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the group.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'occurrence' => $schema->string()->description('The identifier of the date, as list-group-reminder-occurrences gives it.')->required(),
            'answer' => $schema->string()->description('remembered or forgotten.')->required(),
        ];
    }
}
