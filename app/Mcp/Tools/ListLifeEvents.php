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

#[Description('List the life events of one vault, the most recent first, a page at a time, optionally only those one contact took part in, of one type, or in one slice: what is upcoming, nearest first, everything before today, or one year before today. list-life-event-slices counts each slice. The values of sensitive fields are given only to a key holding the sensitive fields scope.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class ListLifeEvents extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEvents';

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
            'contact' => $schema->string()->description('The id of a contact, as list-contacts gives it. Only the life events this contact took part in are listed.'),
            'life_event_type' => $schema->string()->description('The id of a life event type, as list-life-event-types gives it. Only life events of this type are listed.'),
            'slice' => $schema->string()->description('upcoming, for what is dated today or later, nearest first, past, for everything before today, most recent first, or a year such as 2024, for that year up to yesterday, most recent first.'),
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many life events a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
