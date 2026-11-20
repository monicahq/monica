<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\AgentFeedbackKindEnum;
use App\Enums\AgentFeedbackSeverityEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Report a problem with this MCP server to the administrators of this Monica instance: a tool that failed or returned something wrong, a tool that was missing, a description that misled you, or an improvement. Call it at most once per task, and only when a tool got in the way. Never call it to log progress, to test it, or when the task went fine. Paraphrase what the person asked, and use placeholders such as <contact> or <date> instead of names, dates and other details from their vault. Never include secrets.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld]
class SubmitFeedback extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.agentFeedback.create';

    public function handle(Request $request): Response
    {
        return $this->answerThroughApi($request);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'kind' => $schema->string()->enum(AgentFeedbackKindEnum::values())->description('bug: a tool failed or returned something wrong; missing_tool: no tool could do it; unclear_description: a description misled you; improvement: a suggestion.')->required(),
            'severity' => $schema->string()->enum(AgentFeedbackSeverityEnum::values())->description('blocks_task: you could not finish; needs_workaround: you finished another way; minor: you finished, with friction.')->required(),
            'title' => $schema->string()->description('One line naming the problem, at most 200 characters.')->required(),
            'tool' => $schema->string()->description('The tool the report is about, or the name of the tool that is missing.'),
            'request' => $schema->string()->description('What the person asked, paraphrased, without names or details from their vault.'),
            'steps' => $schema->array()->items($schema->object([
                'tool' => $schema->string()->description('The tool you called.')->required(),
                'arguments' => $schema->string()->description('The arguments as JSON, with placeholders instead of vault details.'),
                'outcome' => $schema->string()->enum(['success', 'error'])->required(),
                'result' => $schema->string()->description('What came back, or the error message.'),
            ]))->description('The tool calls you tried, in order, at most 20.'),
            'observed' => $schema->string()->description('What happened, including any error message.')->required(),
            'expected' => $schema->string()->description('What should have happened.')->required(),
            'proposed_fix' => $schema->string()->description('The fix you suggest.')->required(),
            'proposed_code' => $schema->string()->description('Code for the fix, if you have some.'),
            'model' => $schema->string()->description('The model you run on.'),
        ];
    }
}
