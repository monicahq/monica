<?php

declare(strict_types=1);

namespace Tests\Fixtures\Stripe;

use Illuminate\Support\Str;
use RuntimeException;
use Stripe\HttpClient\ClientInterface;

/**
 * Stands in for Stripe's HTTP client so that no test reaches the network.
 *
 * It is installed with `ApiRequestor::setHttpClient()`, which every Stripe client built
 * afterwards reads, answers a canned body per method and path, and records what was sent so a
 * test can assert on it. A request nothing answers raises rather than returning an empty body,
 * because a silent `{}` reaches the code under test as a Stripe object with no fields.
 */
class FakeStripeClient implements ClientInterface
{
    /** @var list<array{method: string, path: string, params: array<string, mixed>}> */
    private array $requests = [];

    /** @var list<array{pattern: string, status: int, body: array<string, mixed>}> */
    private array $responses = [];

    /**
     * The most recently registered answer wins, so a test overrides a default by registering again.
     *
     * @param  array<string, mixed>  $body
     */
    public function respondTo(string $pattern, array $body, int $status = 200): self
    {
        array_unshift($this->responses, ['pattern' => $pattern, 'status' => $status, 'body' => $body]);

        return $this;
    }

    public function failOn(string $pattern): self
    {
        return $this->respondTo($pattern, [
            'error' => ['type' => 'api_error', 'message' => 'Stripe is having a moment.'],
        ], 500);
    }

    /**
     * @param  'delete'|'get'|'post'  $method
     * @param  array<int, string>  $headers
     * @param  array<string, mixed>  $params
     * @param  'v1'|'v2'  $apiMode
     * @return array{0: string, 1: int, 2: array<string, string>}
     */
    #[\Override]
    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $method = strtoupper((string) $method);
        $path = $this->pathOf((string) $absUrl);

        $this->requests[] = ['method' => $method, 'path' => $path, 'params' => $params];

        $answer = $this->answerFor($method, $path);

        return [json_encode($answer['body'], JSON_THROW_ON_ERROR), $answer['status'], []];
    }

    /**
     * Every recorded request, or only those matching `POST checkout/sessions` or `prices*`.
     *
     * @return list<array{method: string, path: string, params: array<string, mixed>}>
     */
    public function requests(?string $pattern = null): array
    {
        if ($pattern === null) {
            return $this->requests;
        }

        return array_values(array_filter(
            $this->requests,
            fn (array $request): bool => $this->matches($pattern, $request['method'], $request['path']),
        ));
    }

    /** @return array{method: string, path: string, params: array<string, mixed>}|null */
    public function lastRequest(string $pattern): ?array
    {
        $requests = $this->requests($pattern);

        return $requests === [] ? null : $requests[count($requests) - 1];
    }

    /** @return array<string, mixed> */
    public function lastParams(string $pattern): array
    {
        return $this->lastRequest($pattern)['params'] ?? [];
    }

    public function sent(string $pattern): bool
    {
        return $this->requests($pattern) !== [];
    }

    /** @return array{status: int, body: array<string, mixed>} */
    private function answerFor(string $method, string $path): array
    {
        foreach ($this->responses as $response) {
            if ($this->matches($response['pattern'], $method, $path)) {
                return ['status' => $response['status'], 'body' => $response['body']];
            }
        }

        throw new RuntimeException("Nothing in the fake Stripe client answers {$method} {$path}.");
    }

    private function matches(string $pattern, string $method, string $path): bool
    {
        if (str_contains($pattern, ' ')) {
            [$wanted, $pattern] = explode(' ', $pattern, 2);

            if (strtoupper($wanted) !== $method) {
                return false;
            }
        }

        return Str::is($pattern, $path);
    }

    private function pathOf(string $absUrl): string
    {
        $path = (string) parse_url($absUrl, PHP_URL_PATH);

        return ltrim(Str::after($path, '/v1'), '/');
    }
}
