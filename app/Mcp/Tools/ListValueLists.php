<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the value lists of the account, the named sets of choices a template field can offer, such as genders or phone labels, each with its choices in order. The id of a list is what create-template-field and update-template-field take as value_list_id.')]
#[IsReadOnly]
class ListValueLists extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.valueLists';

    public function handle(Request $request): Response
    {
        return $this->answerThroughApi($request);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        $default = (int) config('monica.api_default_items_per_page');
        $maximum = (int) config('monica.api_maximum_items_per_page');

        return [
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many lists a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
