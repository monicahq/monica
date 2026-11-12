<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin SupportMessage */
class SupportMessageResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'support_message',
            'id' => $this->uuid,
            'attributes' => [
                'kind' => $this->kind->value,
                'author_name' => $this->author_name,
                'is_support_team' => $this->isFromSupportTeam(),
                'body' => $this->body,
                'attachments' => $this->attachments->map(fn (SupportAttachment $attachment): array => [
                    'id' => $attachment->uuid,
                    'name' => $attachment->original_name,
                    'mime_type' => $attachment->mime_type,
                    'size_in_bytes' => $attachment->size_in_bytes,
                ])->values()->all(),
                'created_at' => $this->created_at->timestamp,
            ],
        ];
    }
}
