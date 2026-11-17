<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Helpers\ReminderDates;
use App\Helpers\VaultReminderDates;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the dates of a whole vault that fall in a window: what is overdue, what falls today and what comes in the next 7 days unless days asks for more (up to 365), for every contact, group and record of the vault, as the vault dashboard shows them. An empty list means nothing falls in that window, not that the vault has no reminders: list-vault-reminders lists every reminder whatever its date. Ask kind=birthdays to know whose birthday it is today or soon: it also lists the birthdays nobody asked to be reminded of, with the age being reached when the year of birth is known.')]
#[IsReadOnly]
class ListVaultReminderOccurrences extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.reminderOccurrences';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);

        return $this->answerThroughApi($request, $vault->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        $default = (int) config('monica.api_default_items_per_page');
        $maximum = (int) config('monica.api_maximum_items_per_page');

        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'category' => $schema->string()->enum(ReminderDates::categories())->description('Only one kind of date: overdue, today, upcoming or history. Overdue, today and upcoming together when left out.'),
            'kind' => $schema->string()->enum(VaultReminderDates::kinds())->description('all for every reminder, birthdays for birthdays only. all when left out.'),
            'days' => $schema->integer()->description('How many days ahead upcoming reaches, from 1 to 365. 7 when left out, as on the vault dashboard.'),
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many dates a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
