<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateContactReminder as CreateContactReminderAction;
use App\Enums\ReminderCadenceEnum;
use App\Exceptions\ReminderIsNotValid;
use App\Http\Resources\ReminderResource;
use App\Models\Reminder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Write a reminder about a contact with a date of its own. It is told on its day, once on each channel the account switched on, and never ahead of it. The change is written to the account journal.')]
class CreateContactReminder extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.reminders.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'cadence' => ['required', Rule::in(ReminderCadenceEnum::values())],
            'interval' => ['nullable', 'integer', 'min:1', 'max:'.Reminder::MAXIMUM_INTERVAL],
            'time' => ['required', 'date_format:H:i'],
            'timezone' => ['nullable', 'string', 'timezone'],
        ]);

        $contact = $this->contact($this->vault($request, $validated['vault']), $validated['contact']);

        try {
            $reminder = new CreateContactReminderAction(
                user: $this->user($request),
                contact: $contact,
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

        return Response::json(new ReminderResource($this->reminder($contact, $reminder->uuid))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the contact.')->required(),
            'contact' => $schema->string()->description('The address of the contact.')->required(),
            'name' => $schema->string()->description('What the reminder says.')->required(),
            'starts_on' => $schema->string()->description('The first date, as YYYY-MM-DD. It cannot be before today.')->required(),
            'cadence' => $schema->string()->description('How often it comes back: '.implode(', ', ReminderCadenceEnum::values()).'.')->required(),
            'interval' => $schema->integer()->description('How many of those between two dates, from 1 to 99.'),
            'time' => $schema->string()->description('The local hour to be told at, as HH:MM.')->required(),
            'timezone' => $schema->string()->description('The time zone that hour is read in. The user\'s own when left out.'),
        ];
    }
}
