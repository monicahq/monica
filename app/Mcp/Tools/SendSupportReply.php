<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\SendSupportReply as SendSupportReplyAction;
use App\Enums\SupportRequestStatusEnum;
use App\Http\Resources\SupportMessageResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('For an administrator of the instance: answer a support request. The reply is emailed to the user, shown in their request and signed with the administrator name, and the request moves to the status given (waiting_on_user by default).')]
class SendSupportReply extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.admin.supportRequests.replies.create';

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
            'body' => ['required', 'string', 'max:20000'],
            'status' => ['nullable', Rule::enum(SupportRequestStatusEnum::class)],
        ]);

        $reply = new SendSupportReplyAction(
            administrator: $this->user($request),
            supportRequest: $this->supportRequest($request, $validated['support_request'], acrossAccounts: true),
            body: $validated['body'],
            statusAfter: SupportRequestStatusEnum::tryFrom((string) ($validated['status'] ?? '')) ?? SupportRequestStatusEnum::WaitingOnUser,
        )->execute();

        return Response::json(new SupportMessageResource($reply->load('attachments'))->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'support_request' => $schema->string()->description('The id of the support request, as list-support-queue gives it.')->required(),
            'body' => $schema->string()->description('The reply, as plain text.')->required(),
            'status' => $schema->string()->enum(SupportRequestStatusEnum::values())->description('Where the request moves after the reply.'),
        ];
    }
}
