<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\NotificationChannel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * What a channel looks like from outside.
 *
 * No channel asks the account for a credential, so there is none to leave out.
 *
 * @mixin NotificationChannel
 */
class NotificationChannelResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'notification_channel',
            'id' => $this->channel->value,
            'attributes' => [
                'channel' => $this->channel->value,
                'label' => __($this->channel->label()),
                'is_enabled' => $this->is_enabled,
                'destination' => $this->destination,
                'needs_destination' => $this->channel->needsDestination(),
                'discovers_destination' => $this->channel->discoversDestination(),
                'requires_proof' => $this->channel->provesSilently(),
                'is_proven' => $this->isProven(),
                'is_configured_on_the_instance' => $this->channel->isConfiguredOnTheInstance(),
                'disabled_reason' => $this->disabled_reason,
            ],
            'links' => [
                'self' => route('api.v1.notificationChannels.show', $this->channel->value),
            ],
        ];
    }
}
