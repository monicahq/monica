<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\SupportRequestStatusEnum;
use App\Helpers\SupportAvailability;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the support requests the account has opened, the most recently updated first, a page at a time: number, subject, category and status. A status of waiting_on_user means the support team has replied.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class ListSupportRequests extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.supportRequests';

    public function shouldRegister(Request $request): bool
    {
        return $request->user() !== null && SupportAvailability::isEnabled();
    }

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
            'status' => $schema->string()->enum(SupportRequestStatusEnum::values())->description('Only the requests in this status.'),
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many requests a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
