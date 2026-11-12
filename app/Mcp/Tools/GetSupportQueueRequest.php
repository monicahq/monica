<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Http\Resources\Admin\SupportRequestResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('For an administrator of the instance: read one support request of any account with its whole thread, internal notes included, a few facts about the account, and how its status moved.')]
#[IsReadOnly]
class GetSupportQueueRequest extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.admin.supportRequests.show';

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

        return Response::json(new SupportRequestResource($supportRequest->load(['user', 'messages.attachments', 'statusChanges']))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'support_request' => $schema->string()->description('The id of the support request, as list-support-queue gives it.')->required(),
        ];
    }
}
