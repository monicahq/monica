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

#[Description('For an administrator of the instance: leave a note on an agent feedback report for the other administrators. Nobody else reads it. The status does not change.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class AddAgentFeedbackNote extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.admin.agentFeedback.notes.create';

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
            'body' => $schema->string()->description('The note, as plain text.')->required(),
        ];
    }
}
