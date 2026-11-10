<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\ReminderDates;
use App\Http\Resources\ReminderOccurrenceResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the dates the reminders about a contact produced, with what the user answered for each: remembered, forgotten, or no answer. A missing answer never means forgotten, and a notification sent or read never means remembered.')]
#[IsReadOnly]
class ListContactReminderOccurrences extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts.reminderOccurrences';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'contact' => ['required', 'string'],
            'category' => ['nullable', 'string', Rule::in(ReminderDates::categories())],
            'page' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer'],
        ]);

        $perPage = max(1, min(
            (int) ($validated['per_page'] ?? config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $contact = $this->contact($this->vault($request, $validated['vault']), $validated['contact']);
        $dates = ReminderDates::page(
            user: $this->user($request),
            record: $contact,
            category: $validated['category'] ?? null,
            perPage: $perPage,
            page: max(1, (int) ($validated['page'] ?? 1)),
        );

        return Response::json([
            'data' => ReminderOccurrenceResource::collection($dates->items())->resolve(),
            'meta' => [
                'page' => $dates->currentPage(),
                'per_page' => $dates->perPage(),
                'total' => $dates->total(),
            ],
        ]);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the contact.')->required(),
            'contact' => $schema->string()->description('The address of the contact.')->required(),
            'category' => $schema->string()->description('Only one kind of date: overdue, today, upcoming or history. Every date, newest first, when left out.'),
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description('How many dates a page holds.'),
        ];
    }
}
