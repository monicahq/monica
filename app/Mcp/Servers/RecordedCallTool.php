<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Enums\McpCallResultEnum;
use App\Enums\McpOperationEnum;
use App\Helpers\McpPayload;
use App\Models\McpCall;
use App\Models\User;
use App\Models\Vault;
use Generator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Exceptions\JsonRpcException;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Contracts\Errable;
use Laravel\Mcp\Server\Contracts\Method;
use Laravel\Mcp\Server\Methods\Concerns\InteractsWithResponses;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Transport\JsonRpcRequest;
use Laravel\Mcp\Transport\JsonRpcResponse;
use Throwable;

/**
 * The `tools/call` method of the MCP server, recording every call in the MCP history.
 *
 * It does what the package's `CallTool` and `ToolInvoker` do, and keeps the exception a tool raised
 * before the package turns it into plain text, since that text no longer says whether the call was
 * refused, given wrong parameters or broke. The row is written before the tool runs, as
 * interrupted, so a call that never returns can never read as a success. A request with no API
 * key or connected assistant behind it is answered without being recorded.
 */
class RecordedCallTool implements Errable, Method
{
    use InteractsWithResponses {
        toErrorResponse as private convertToErrorResponse;
    }

    private ?Throwable $failure = null;

    /**
     * @return JsonRpcResponse|Generator<JsonRpcResponse>
     *
     * @throws JsonRpcException
     */
    public function handle(JsonRpcRequest $request, ServerContext $context): Generator|JsonRpcResponse
    {
        $name = $request->get('name');
        $tool = $context->tools()->first(fn (Tool $tool): bool => $tool->name() === $name);
        $call = $this->open($request, is_string($name) ? $name : '', $tool);
        $startedAt = hrtime(true);

        if ($tool === null) {
            $message = is_null($name) ? 'Missing [name] parameter.' : "Tool [{$name}] not found.";

            $call?->update([
                'result' => McpCallResultEnum::InvalidParameters,
                'error_code' => -32602,
                'error_message' => $message,
                'duration_in_milliseconds' => $this->millisecondsSince($startedAt),
            ]);

            throw new JsonRpcException($message, -32602, $request->id);
        }

        $response = $this->callHandler(fn (): mixed => Container::getInstance()->call([$tool, 'handle']), $request);

        if (is_iterable($response)) {
            return $this->streamAndClose($call, $startedAt, $this->toJsonRpcStreamedResponse($request, $response, $this->serializable($tool)));
        }

        $answer = $this->toJsonRpcResponse($request, $response, $this->serializable($tool));

        $this->close($call, $startedAt, $answer);

        return $answer;
    }

    protected function toErrorResponse(Throwable $e): Response
    {
        $this->failure = $e;

        return $this->convertToErrorResponse($e);
    }

    protected function serializable(Tool $tool): callable
    {
        return fn (ResponseFactory $factory): array => $factory->mergeStructuredContent(
            $factory->mergeMeta([
                'content' => $factory->responses()->map(fn (Response $response): array => $response->content()->toTool($tool))->all(),
                'isError' => $factory->responses()->contains(fn (Response $response): bool => $response->isError()),
            ])
        );
    }

    private function open(JsonRpcRequest $request, string $name, ?Tool $tool): ?McpCall
    {
        $http = request();
        $user = $http->user();
        $connectionType = $http->attributes->get('mcp_connection_type');

        $http->attributes->remove('mcp_vault');
        $http->attributes->remove('mcp_subject');

        if (! $user instanceof User || $connectionType === null) {
            return null;
        }

        $arguments = $request->params['arguments'] ?? [];
        $parameters = McpPayload::encode(McpPayload::mask(is_array($arguments) ? $arguments : [$arguments], $user->account, $name));

        return McpCall::query()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'connection_type' => $connectionType,
            'connection_id' => $http->attributes->get('mcp_connection_id'),
            'connection_name' => $http->attributes->get('mcp_connection_name') ?? '',
            'tool' => $name,
            'operation' => $this->operation($tool),
            'result' => McpCallResultEnum::Interrupted,
            'parameters' => $parameters['text'],
            'parameters_truncated' => $parameters['truncated'],
            'json_rpc_id' => (string) $request->id,
            'ip_address' => $http->ip(),
            'started_at' => now(),
        ]);
    }

    private function operation(?Tool $tool): ?McpOperationEnum
    {
        if ($tool === null || ! method_exists($tool, 'mirrors')) {
            return null;
        }

        $route = Route::getRoutes()->getByName($tool->mirrors());

        return $route === null ? null : McpOperationEnum::fromHttpMethod(implode('|', $route->methods()));
    }

    /**
     * @param  Generator<JsonRpcResponse>  $messages
     * @return Generator<JsonRpcResponse>
     */
    private function streamAndClose(?McpCall $call, int|float $startedAt, Generator $messages): Generator
    {
        $last = null;

        foreach ($messages as $message) {
            $last = $message;

            yield $message;
        }

        if ($last !== null) {
            $this->close($call, $startedAt, $last);
        }
    }

    private function close(?McpCall $call, int|float $startedAt, JsonRpcResponse $answer): void
    {
        if ($call === null) {
            return;
        }

        /** @var array{content?: list<array<string, mixed>>, isError?: bool} $result */
        $result = $answer->content['result'] ?? [];
        $isError = $result['isError'] ?? false;
        $response = McpPayload::encode(McpPayload::mask($result, $call->account, $call->tool));
        $vault = request()->attributes->get('mcp_vault');
        $subject = request()->attributes->get('mcp_subject');
        $reachedVault = $vault instanceof Vault && $vault->account_id === $call->account_id ? $vault : null;
        $reachedSubject = $subject instanceof Model ? $subject : null;

        $call->update([
            'result' => $this->result($isError),
            'error_code' => $isError ? ($this->failure instanceof ValidationException ? -32602 : -32603) : null,
            'error_message' => $isError ? $this->errorMessage($result['content'] ?? []) : null,
            'vault_id' => $reachedVault?->id,
            'vault_name' => $reachedVault?->name,
            'subject_type' => $reachedSubject?->getMorphClass(),
            'subject_id' => $reachedSubject?->getKey(),
            'subject_label' => $reachedSubject === null ? null : $this->label($reachedSubject),
            'response' => $response['text'],
            'response_truncated' => $response['truncated'],
            'duration_in_milliseconds' => $this->millisecondsSince($startedAt),
        ]);
    }

    private function result(bool $isError): McpCallResultEnum
    {
        if (! $isError) {
            return McpCallResultEnum::Succeeded;
        }

        return match (true) {
            $this->failure === null => McpCallResultEnum::Refused,
            $this->failure instanceof ValidationException && array_keys($this->failure->errors()) === ['address'] => McpCallResultEnum::Refused,
            $this->failure instanceof ValidationException => McpCallResultEnum::InvalidParameters,
            $this->failure instanceof AuthorizationException, $this->failure instanceof AuthenticationException => McpCallResultEnum::Refused,
            default => McpCallResultEnum::Error,
        };
    }

    /** @param  list<array<string, mixed>>  $content */
    private function errorMessage(array $content): ?string
    {
        $texts = array_filter(array_map(fn (array $part): mixed => $part['text'] ?? null, $content), is_string(...));

        return $texts === [] ? null : implode("\n", $texts);
    }

    private function label(Model $record): ?string
    {
        foreach (['name', 'label', 'title', 'subject'] as $attribute) {
            $value = $record->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return mb_substr($value, 0, 255);
            }
        }

        return null;
    }

    private function millisecondsSince(int|float $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
