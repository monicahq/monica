<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChangelogComment;
use App\Models\ChangelogCommentReply;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin ChangelogComment */
class ChangelogCommentResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'changelog_comment',
            'id' => $this->uuid,
            'attributes' => [
                'author_name' => $this->is_anonymous ? __('Anonymous') : $this->user->name,
                'is_mine' => $this->user_id === $request->user()?->id,
                'is_private' => $this->is_private,
                'is_anonymous' => $this->is_anonymous,
                'body' => $this->body,
                'replies' => $this->replies
                    ->filter(fn (ChangelogCommentReply $reply): bool => $reply->is_public)
                    ->map(fn (ChangelogCommentReply $reply): array => [
                        'author_name' => $reply->author_name,
                        'body' => $reply->body,
                        'created_at' => $reply->created_at->timestamp,
                    ])
                    ->values()
                    ->all(),
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
        ];
    }
}
