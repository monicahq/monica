<?php

namespace Tests\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Assert;

class GuzzleMock extends Assert
{
    private array $container = [];

    private array $responses = [];

    private Client $client;

    public function __construct($responses)
    {
        $this->responses = $responses;

        $history = Middleware::history($this->container);
        $stack = MockHandler::createWithMiddleware(array_map(function ($item) {
            if (is_array($item)) {
                return new Response(200, [], json_encode($item));
            }

            return $item;
        }, $responses));
        $stack->push($history);

        $this->client = new Client(['handler' => $stack]);
    }

    /**
     * Get current client.
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    public function assertResponses(): void
    {
        $nb = count($this->container);

        $this->assertCount($nb, $this->responses);
        $responses = array_keys($this->responses);

        for ($i = 0; $i < $nb; $i++) {
            $this->assertEquals($responses[$i], (string) $this->container[$i]['request']->getUri());
        }
    }
}
