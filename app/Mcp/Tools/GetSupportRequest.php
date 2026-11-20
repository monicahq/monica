<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

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

#[Description('Read one support request of the account with its whole thread: what the user wrote, the replies of the support team, and the names and sizes of the files sent. The content of a file is not returned.')]
#[IsReadOnly]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class GetSupportRequest extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.supportRequests.show';

    public function shouldRegister(Request $request): bool
    {
        return $request->user() !== null && SupportAvailability::isEnabled();
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'support_request' => ['required', 'string'],
        ]);

        $supportRequest = $this->supportRequest($request, $validated['support_request']);

        return $this->answerThroughApi($request, $supportRequest->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'support_request' => $schema->string()->description('The id of the support request, as list-support-requests gives it.')->required(),
        ];
    }
}
