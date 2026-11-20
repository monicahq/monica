<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List every reminder of a vault, about its contacts and its groups, whatever its date: what it says, whom it is about, how often it comes back and its next date. Use it to answer "what are my reminders". To know what falls in the coming days instead, use list-vault-reminder-occurrences.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class ListVaultReminders extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.reminders';

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
            'about' => $schema->string()->enum(['contact', 'group'])->description('Only the reminders about contacts, or only those about groups. Both when left out.'),
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many reminders a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
