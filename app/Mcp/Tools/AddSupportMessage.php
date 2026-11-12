<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\AddSupportMessage as AddSupportMessageAction;
use App\Helpers\SupportAvailability;
use App\Http\Resources\SupportMessageResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Write to the support team in a request already opened. The request goes back to the support team, and a solved request is reopened under the same number. Files cannot be sent from here.')]
class AddSupportMessage extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.supportRequests.messages.create';

    public function shouldRegister(Request $request): bool
    {
        return $request->user() !== null && SupportAvailability::isEnabled();
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'support_request' => ['required', 'string'],
            'body' => ['required', 'string', 'max:20000'],
        ]);

        $message = new AddSupportMessageAction(
            user: $this->user($request),
            supportRequest: $this->supportRequest($request, $validated['support_request']),
            body: $validated['body'],
        )->execute();

        return Response::json(new SupportMessageResource($message->load('attachments'))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'support_request' => $schema->string()->description('The id of the support request, as list-support-requests gives it.')->required(),
            'body' => $schema->string()->description('What to write, as plain text.')->required(),
        ];
    }
}
