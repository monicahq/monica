<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\SolveSupportRequest as SolveSupportRequestAction;
use App\Helpers\SupportAvailability;
use App\Http\Resources\SupportRequestResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Tell the support team a request needs nothing more. Writing in it again reopens it.')]
class SolveSupportRequest extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.supportRequests.solution.create';

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

        try {
            new SolveSupportRequestAction(user: $this->user($request), supportRequest: $supportRequest)->execute();
        } catch (AuthorizationException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json(new SupportRequestResource($supportRequest)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'support_request' => $schema->string()->description('The id of the support request, as list-support-requests gives it.')->required(),
        ];
    }
}
