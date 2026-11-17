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

#[Description('For an administrator of the instance: list the support requests of every account, oldest first, in one view (to_handle by default, waiting_on_user, solved or all), with the count of each view. Filter by text, category, user id or age (day, week, old).')]
#[IsReadOnly]
class ListSupportQueue extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.admin.supportRequests';

    public function shouldRegister(Request $request): bool
    {
        return $this->administers($request);
    }

    public function handle(Request $request): Response
    {
        if (! $this->administers($request)) {
            throw $this->doesNotExist(__('That support request does not exist.'));
        }

        return $this->answerThroughApi($request);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        $default = (int) config('monica.api_default_items_per_page');
        $maximum = (int) config('monica.api_maximum_items_per_page');

        return [
            'view' => $schema->string()->enum(['to_handle', 'waiting_on_user', 'solved', 'all'])->description('Which requests to list.'),
            'q' => $schema->string()->description('Words found in the subject, a message, the name or the email address of the user, or the request number.'),
            'category' => $schema->string()->enum(['feature', 'billing', 'email', 'question', 'technical'])->description('Only this category.'),
            'user' => $schema->integer()->description('Only the requests of this user id.'),
            'age' => $schema->string()->enum(['day', 'week', 'old'])->description('Opened in the last day, between one and seven days ago, or longer ago.'),
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many requests a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
