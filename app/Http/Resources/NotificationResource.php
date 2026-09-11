<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Notification */
class NotificationResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'notification',
            'id' => $this->uuid,
            'attributes' => [
                'notification_type' => $this->type->value,
                'channel' => $this->channel->value,
                'title' => $this->title,
                'body' => $this->body,
                'scheduled_for' => $this->scheduled_for->timestamp,
                'sent_at' => $this->sent_at?->timestamp,
                'read_at' => $this->read_at?->timestamp,
                'failure_reason' => $this->failure_reason,
                'created_at' => $this->created_at->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.notifications.show', $this->uuid),
            ],
        ];
    }
}
