<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\EmailSent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin EmailSent */
class EmailSentResource extends JsonResource
{
    public function __construct(
        EmailSent $resource,
        private readonly bool $withBody = false,
    ) {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'email_sent',
            'id' => (string) $this->id,
            'attributes' => [
                'email_type' => $this->email_type,
                'subject' => $this->subject,
                'email_address' => $this->email_address,
                'body' => $this->when($this->withBody, fn (): ?string => $this->body),
                'delivery_status' => $this->delivery_status->value,
                'sent_at' => $this->sent_at?->timestamp,
                'delivered_at' => $this->delivered_at?->timestamp,
                'bounced_at' => $this->bounced_at?->timestamp,
                'complained_at' => $this->complained_at?->timestamp,
                'created_at' => $this->created_at->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.emailsSent.show', $this->id),
            ],
        ];
    }
}
