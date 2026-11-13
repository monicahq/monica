<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\ReminderDates;
use App\Helpers\VaultReminderDates;
use App\Http\Resources\VaultReminderDateResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the dates of a whole vault: what is overdue, what falls today and what comes in the next days, for every contact, group and record of the vault, as the vault dashboard shows them. Ask kind=birthdays to know whose birthday it is today or soon: it also lists the birthdays nobody asked to be reminded of, with the age being reached when the year of birth is known.')]
#[IsReadOnly]
class ListVaultReminderOccurrences extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.reminderOccurrences';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'category' => ['nullable', 'string', Rule::in(ReminderDates::categories())],
            'kind' => ['nullable', 'string', Rule::in(VaultReminderDates::kinds())],
            'days' => ['nullable', 'integer', 'min:1', 'max:'.VaultReminderDates::MaximumDays],
            'page' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer'],
        ]);

        $perPage = max(1, min(
            (int) ($validated['per_page'] ?? config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $dates = VaultReminderDates::page(
            user: $this->user($request),
            vault: $this->vault($request, $validated['vault']),
            category: $validated['category'] ?? null,
            kind: $validated['kind'] ?? VaultReminderDates::All,
            days: (int) ($validated['days'] ?? VaultReminderDates::DefaultDays),
            reachesSensitiveFields: $this->reachesSensitiveFields($request),
            perPage: $perPage,
            page: max(1, (int) ($validated['page'] ?? 1)),
        );

        return Response::json([
            'data' => VaultReminderDateResource::collection($dates->items())->resolve(),
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
            'vault' => $schema->string()->description('The address of the vault.')->required(),
            'category' => $schema->string()->description('Only one kind of date: overdue, today, upcoming or history. Overdue, today and upcoming together when left out.'),
            'kind' => $schema->string()->description('all for every reminder, birthdays for birthdays only. all when left out.'),
            'days' => $schema->integer()->description('How many days ahead upcoming reaches, from 1 to 365. 7 when left out, as on the vault dashboard.'),
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description('How many dates a page holds.'),
        ];
    }
}
