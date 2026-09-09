<?php

declare(strict_types=1);

namespace Tests\Fixtures\Stripe;

/**
 * A webhook payload signed the way Stripe signs one, so that a test can prove the endpoint acts on
 * a confirmation Stripe really sent and refuses one it did not.
 */
class SignedWebhookRequest
{
    /** @param  array<string, mixed>  $payload */
    public function __construct(
        private readonly array $payload,
        private readonly string $secret,
        private ?int $timestamp = null,
    ) {
        $this->timestamp ??= time();
    }

    public function body(): string
    {
        return json_encode($this->payload, JSON_THROW_ON_ERROR);
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return [
            'Stripe-Signature' => "t={$this->timestamp},v1={$this->signature()}",
            'Content-Type' => 'application/json',
        ];
    }

    /** @return array<string, string> */
    public function headersSignedByAnImpostor(): array
    {
        return [
            'Stripe-Signature' => "t={$this->timestamp},v1=".hash('sha256', 'dwight'),
            'Content-Type' => 'application/json',
        ];
    }

    /** @return array<string, string> */
    public function headersWithNoSignature(): array
    {
        return ['Content-Type' => 'application/json'];
    }

    private function signature(): string
    {
        return hash_hmac('sha256', "{$this->timestamp}.{$this->body()}", $this->secret);
    }
}
