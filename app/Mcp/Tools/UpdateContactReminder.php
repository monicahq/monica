<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateReminder;
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

#[Description('Change what a reminder about a contact says and when it comes back. Dates after today nobody answered are replaced; answers already given never change. The change is written to the account journal.')]
class UpdateContactReminder extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.reminders.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'reminder' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'cadence' => ['required', Rule::in(ReminderCadenceEnum::values())],
            'interval' => ['nullable', 'integer', 'min:1', 'max:'.Reminder::MAXIMUM_INTERVAL],
            'time' => ['required', 'date_format:H:i'],
            'timezone' => ['nullable', 'string', 'timezone'],
        ]);

        $contact = $this->contact($this->vault($request, $validated['vault']), $validated['contact']);
        $reminder = $this->reminder($contact, $validated['reminder']);

        try {
            new UpdateReminder(
                user: $this->user($request),
                reminder: $reminder,
                cadence: ReminderCadenceEnum::from($validated['cadence']),
                timeOfDay: $validated['time'],
                name: $validated['name'],
                frequencyInterval: (int) ($validated['interval'] ?? $reminder->frequency_interval),
                timezone: $validated['timezone'] ?? null,
                startsOn: $validated['starts_on'] ?? null,
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
            'reminder' => $schema->string()->description('The identifier of the reminder, as list-contact-reminders gives it.')->required(),
            'name' => $schema->string()->description('What the reminder says.')->required(),
            'starts_on' => $schema->string()->description('A new first date, as YYYY-MM-DD, for a reminder written by hand only.'),
            'cadence' => $schema->string()->description('How often it comes back: '.implode(', ', ReminderCadenceEnum::values()).'.')->required(),
            'interval' => $schema->integer()->description('How many of those between two dates, from 1 to 99. The current one is kept when left out.'),
            'time' => $schema->string()->description('The local hour to be told at, as HH:MM.')->required(),
            'timezone' => $schema->string()->description('A new time zone. The current one is kept when left out.'),
        ];
    }
}
