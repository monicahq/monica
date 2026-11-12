<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SupportMessage;
use App\Models\SupportRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * A support request as its author reaches it. The thread is read through `visibleMessages`, so an
 * internal note can never be part of it.
 *
 * @mixin SupportRequest
 */
class SupportRequestResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'support_request',
            'id' => $this->uuid,
            'attributes' => [
                'reference' => $this->reference,
                'category' => $this->category->value,
                'subject' => $this->subject,
                'status' => $this->status->value,
                'status_label' => __($this->status->userLabel()),
                'opened_at' => $this->created_at->timestamp,
                'last_activity_at' => $this->last_activity_at->timestamp,
                'solved_at' => $this->solved_at?->timestamp,
                'messages' => $this->whenLoaded('visibleMessages', fn (): array => $this->visibleMessages
                    ->map(fn (SupportMessage $message): array => new SupportMessageResource($message)->resolve($request))
                    ->values()
                    ->all()),
            ],
            'links' => [
                'self' => route('api.v1.supportRequests.show', $this->uuid),
            ],
        ];
    }
}
