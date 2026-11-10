<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateManualReminder;
use App\Enums\ReminderCadenceEnum;
use App\Exceptions\ReminderIsNotValid;
use App\Helpers\FlagState;
use App\Http\Resources\ReminderResource;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Write a reminder about a group as a whole, with a date of its own. It is told on its day, once on each channel the account switched on, and never ahead of it. The change is written to the account journal.')]
class CreateGroupReminder extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.groups.reminders.create';

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
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'cadence' => ['required', Rule::in(ReminderCadenceEnum::values())],
            'interval' => ['nullable', 'integer', 'min:1', 'max:'.Reminder::MAXIMUM_INTERVAL],
            'time' => ['required', 'date_format:H:i'],
            'timezone' => ['nullable', 'string', 'timezone'],
        ]);

        $group = $this->group($this->vault($request, $validated['vault']), $validated['group']);

        try {
            $reminder = new CreateManualReminder(
                user: $this->user($request),
                record: $group,
                name: $validated['name'],
                startsOn: $validated['starts_on'],
                cadence: ReminderCadenceEnum::from($validated['cadence']),
                timeOfDay: $validated['time'],
                frequencyInterval: (int) ($validated['interval'] ?? 1),
                timezone: $validated['timezone'] ?? null,
            )->execute();
        } catch (ReminderIsNotValid $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new ReminderResource($this->groupReminder($group, $reminder->uuid))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the group.')->required(),
            'group' => $schema->string()->description('The id of the group, as list-groups gives it.')->required(),
            'name' => $schema->string()->description('What the reminder says.')->required(),
            'starts_on' => $schema->string()->description('The first date, as YYYY-MM-DD. It cannot be before today.')->required(),
            'cadence' => $schema->string()->description('How often it comes back: '.implode(', ', ReminderCadenceEnum::values()).'.')->required(),
            'interval' => $schema->integer()->description('How many of those between two dates, from 1 to 99.'),
            'time' => $schema->string()->description('The local hour to be told at, as HH:MM.')->required(),
            'timezone' => $schema->string()->description('The time zone that hour is read in. The user\'s own when left out.'),
        ];
    }
}
