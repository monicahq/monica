<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('For an administrator of the instance: leave an internal note on a support request for the other administrators. The user never sees it and is never told it exists. The status does not change.')]
class AddSupportNote extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.admin.supportRequests.notes.create';

    public function shouldRegister(Request $request): bool
    {
        return $this->administers($request);
    }

    public function handle(Request $request): Response
    {
        if (! $this->administers($request)) {
            throw $this->doesNotExist(__('That support request does not exist.'));
        }

        $validated = $request->validate([
            'support_request' => ['required', 'string'],
        ]);

        $supportRequest = $this->supportRequest($request, $validated['support_request'], acrossAccounts: true);

        return $this->answerThroughApi($request, $supportRequest->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'support_request' => $schema->string()->description('The id of the support request, as list-support-queue gives it.')->required(),
            'body' => $schema->string()->description('The note, as plain text.')->required(),
        ];
    }
}
