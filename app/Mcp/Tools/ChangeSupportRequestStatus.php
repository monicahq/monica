<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\ChangeSupportRequestStatus as ChangeSupportRequestStatusAction;
use App\Enums\SupportRequestStatusEnum;
use App\Http\Resources\Admin\SupportRequestResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('For an administrator of the instance: move a support request to another status (to_handle, waiting_on_user or solved). The move is written to its status history. Nobody is emailed.')]
class ChangeSupportRequestStatus extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.admin.supportRequests.status.update';

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
            'status' => ['required', Rule::enum(SupportRequestStatusEnum::class)],
        ]);

        $supportRequest = new ChangeSupportRequestStatusAction(
            administrator: $this->user($request),
            supportRequest: $this->supportRequest($request, $validated['support_request'], acrossAccounts: true),
            status: SupportRequestStatusEnum::from($validated['status']),
        )->execute();

        return Response::json(new SupportRequestResource($supportRequest)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'support_request' => $schema->string()->description('The id of the support request, as list-support-queue gives it.')->required(),
            'status' => $schema->string()->enum(SupportRequestStatusEnum::values())->description('The status to move it to.')->required(),
        ];
    }
}
