<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\ContactGroupingEnum;
use App\Enums\ContactSortEnum;
use App\Enums\SortDirectionEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the contacts of one vault, a page at a time, optionally narrowed by a search, a grouping or contact categories. Each contact comes with the columns the vault shows in its list.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class ListContacts extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.contacts';

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
            'query' => $schema->string()->description('Words to search the contacts of the vault for.'),
            'filter' => $schema->string()->enum(ContactGroupingEnum::values())->description('Only the contacts of one grouping.'),
            'sort' => $schema->string()->enum(ContactSortEnum::values())->description('What the list is ordered by.'),
            'direction' => $schema->string()->enum(SortDirectionEnum::values())->description('The direction of the order.'),
            'categories' => $schema->array()->items($schema->integer())->description('The ids of contact categories, as list-contact-categories gives them; only the people holding any of them.'),
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many contacts a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
