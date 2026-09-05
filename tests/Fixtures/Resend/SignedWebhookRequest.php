<?php

declare(strict_types=1);

namespace Tests\Fixtures\Resend;

class SignedWebhookRequest
{
    /** @param  array<string, mixed>  $payload */
    public function __construct(
        private readonly array $payload,
        private readonly string $secret,
        private readonly string $id = 'msg_dundermifflin',
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
            'svix-id' => $this->id,
            'svix-timestamp' => (string) $this->timestamp,
            'svix-signature' => 'v1,'.$this->signature(),
        ];
    }

    /** @return array<string, string> */
    public function headersSignedByAnImpostor(): array
    {
        return [
            ...$this->headers(),
            'svix-signature' => 'v1,'.base64_encode(hash('sha256', 'dwight', true)),
        ];
    }

    private function signature(): string
    {
        $key = base64_decode(str_starts_with($this->secret, 'whsec_')
            ? substr($this->secret, strlen('whsec_'))
            : $this->secret);

        $hash = hash_hmac('sha256', "{$this->id}.{$this->timestamp}.{$this->body()}", $key);

        return base64_encode(pack('H*', $hash));
    }
}
