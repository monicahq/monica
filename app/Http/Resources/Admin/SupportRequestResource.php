<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Helpers\SupportRequestFacts;
use App\Http\Resources\SupportMessageResource;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\SupportStatusChange;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * A support request as the administrators reach it: the whole thread with the internal notes, who
 * asked, a few facts about their account, and how its status moved.
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
                'account_id' => $this->account_id,
                'user' => [
                    'name' => $this->user?->name,
                    'email' => $this->user?->email,
                ],
                'category' => $this->category->value,
                'subject' => $this->subject,
                'status' => $this->status->value,
                'status_label' => __($this->status->administratorLabel()),
                'application_version' => $this->application_version,
                'locale' => $this->locale,
                'opened_at' => $this->created_at->timestamp,
                'last_activity_at' => $this->last_activity_at->timestamp,
                'solved_at' => $this->solved_at?->timestamp,
                'messages' => $this->whenLoaded('messages', fn (): array => $this->messages
                    ->map(fn (SupportMessage $message): array => new SupportMessageResource($message)->resolve($request))
                    ->values()
                    ->all()),
                'context' => $this->whenLoaded('messages', fn (): array => SupportRequestFacts::of($this->resource)),
                'status_changes' => $this->whenLoaded('statusChanges', fn (): array => $this->statusChanges
                    ->map(fn (SupportStatusChange $change): array => [
                        'from' => $change->from_status?->value,
                        'to' => $change->to_status->value,
                        'by' => $change->user_name,
                        'at' => $change->created_at->timestamp,
                    ])
                    ->values()
                    ->all()),
            ],
            'links' => [
                'self' => route('api.v1.admin.supportRequests.show', $this->uuid),
            ],
        ];
    }
}
