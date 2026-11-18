<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\AgentFeedbackStatusEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('For an administrator of the instance: move an agent feedback report. A new report can be accepted or dismissed, an accepted one fixed or dismissed, and a fixed or dismissed one reopened to new. The move is written to the administrator log.')]
class ChangeAgentFeedbackStatus extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.admin.agentFeedback.status.update';

    public function shouldRegister(Request $request): bool
    {
        return $this->administersInstance($request);
    }

    public function handle(Request $request): Response
    {
        if (! $this->administersInstance($request)) {
            throw $this->doesNotExist(__('That report does not exist.'));
        }

        $validated = $request->validate([
            'report' => ['required', 'string'],
        ]);

        return $this->answerThroughApi($request, $this->agentFeedbackReport($validated['report'])->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'report' => $schema->string()->description('The id of the report, as list-agent-feedback gives it.')->required(),
            'status' => $schema->string()->enum(AgentFeedbackStatusEnum::values())->description('The status to move the report to.')->required(),
        ];
    }
}
