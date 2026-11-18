<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Ask Stripe for its confirmation endpoints, which also proves the secret key is accepted, and say
 * which confirmations the instance acts on that no endpoint of this instance sends. Changes nothing.
 */
class CheckStripeConnection
{
    private const string ENDPOINTS_URL = 'https://api.stripe.com/v1/webhook_endpoints';

    /**
     * @return array{
     *     connection: string,
     *     endpointsReadable: bool,
     *     endpoints: list<array{url: ?string, isThisInstance: bool, isEnabled: bool}>,
     *     missingEvents: list<string>
     * }
     */
    public function execute(): array
    {
        $status = [
            'connection' => 'notConfigured',
            'endpointsReadable' => false,
            'endpoints' => [],
            'missingEvents' => [],
        ];

        $secret = config('cashier.secret');

        if (! is_string($secret) || $secret === '') {
            return $status;
        }

        try {
            $response = Http::withToken($secret)->connectTimeout(2)->timeout(3)->get(self::ENDPOINTS_URL, ['limit' => 100]);
        } catch (ConnectionException) {
            $status['connection'] = 'unreachable';

            return $status;
        }

        if ($response->status() === 401) {
            $status['connection'] = 'refused';

            return $status;
        }

        if ($response->status() === 403) {
            $status['connection'] = 'reachable';

            return $status;
        }

        if (! $response->successful()) {
            $status['connection'] = 'unreachable';

            return $status;
        }

        $status['connection'] = 'reachable';
        $status['endpointsReadable'] = true;
        $status['endpoints'] = $this->endpoints($response->json('data'));
        $status['missingEvents'] = $this->missingEvents($response->json('data'));

        return $status;
    }

    /** @return list<array{url: ?string, isThisInstance: bool, isEnabled: bool}> */
    private function endpoints(mixed $data): array
    {
        $endpoints = [];

        foreach (is_array($data) ? $data : [] as $endpoint) {
            $isThisInstance = $this->isThisInstance($endpoint['url'] ?? null);

            $endpoints[] = [
                'url' => $isThisInstance ? (string) $endpoint['url'] : null,
                'isThisInstance' => $isThisInstance,
                'isEnabled' => ($endpoint['status'] ?? null) === 'enabled',
            ];
        }

        return $endpoints;
    }

    /** @return list<string> */
    private function missingEvents(mixed $data): array
    {
        $covered = [];

        foreach (is_array($data) ? $data : [] as $endpoint) {
            if (! $this->isThisInstance($endpoint['url'] ?? null) || ($endpoint['status'] ?? null) !== 'enabled') {
                continue;
            }

            $covered = [...$covered, ...(array) ($endpoint['enabled_events'] ?? [])];
        }

        if (in_array('*', $covered, true)) {
            return [];
        }

        /** @var list<string> $required */
        $required = config('cashier.webhook.events');

        return array_values(array_diff($required, $covered));
    }

    private function isThisInstance(mixed $url): bool
    {
        return is_string($url) && rtrim($url, '/') === rtrim(route('cashier.webhook'), '/');
    }
}
