<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\AgentFeedbackKindEnum;
use App\Enums\AgentFeedbackSeverityEnum;
use App\Enums\AgentFeedbackStatusEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('For an administrator of the instance: list the reports AI agents sent with submit-feedback, newest first, in one status (new by default, accepted, fixed, dismissed or all), with the count of each status. Filter by text, kind or severity.')]
#[IsReadOnly]
class ListAgentFeedback extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.admin.agentFeedback';

    public function shouldRegister(Request $request): bool
    {
        return $this->administersInstance($request);
    }

    public function handle(Request $request): Response
    {
        if (! $this->administersInstance($request)) {
            throw $this->doesNotExist(__('That report does not exist.'));
        }

        return $this->answerThroughApi($request);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        $default = (int) config('monica.api_default_items_per_page');
        $maximum = (int) config('monica.api_maximum_items_per_page');

        return [
            'status' => $schema->string()->enum([...AgentFeedbackStatusEnum::values(), 'all'])->description('Which reports to list.'),
            'q' => $schema->string()->description('Words found in the title, the tool, or the name or email address of the user.'),
            'kind' => $schema->string()->enum(AgentFeedbackKindEnum::values())->description('Only this kind.'),
            'severity' => $schema->string()->enum(AgentFeedbackSeverityEnum::values())->description('Only this severity.'),
            'page' => $schema->integer()->description('The page to read, starting at 1.'),
            'per_page' => $schema->integer()->description("How many reports a page holds: {$default} unless asked, {$maximum} at most."),
        ];
    }
}
