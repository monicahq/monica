<?php

declare(strict_types=1);

namespace App\ViewModels;

use App\Enums\ChangelogCommentStatusEnum;
use App\Models\ChangelogComment;
use App\Models\ChangelogCommentReply;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class ChangelogCommentsViewModel
{
    /** @var Collection<int, ChangelogComment>|null */
    private ?Collection $comments = null;

    public function __construct(
        private readonly User $user,
        private readonly string $entryId,
    ) {}

    public function entryId(): string
    {
        return $this->entryId;
    }

    public function formUrl(): string
    {
        return route('changelog.comments.create', ['entry' => $this->entryId]);
    }

    public function userName(): string
    {
        return $this->user->name;
    }

    /**
     * Public comments nobody hid, plus the reader's own whatever they are. The author of an
     * anonymous comment is named to nobody but that author.
     *
     * @return list<array{id: string, authorName: string, isMine: bool, isAnonymous: bool, isPrivate: bool, isHidden: bool, body: string, createdAt: CarbonImmutable, replies: list<array{authorName: string, body: string, createdAt: CarbonImmutable}>}>
     */
    public function comments(): array
    {
        return $this->visibleComments()->map(fn (ChangelogComment $comment): array => [
            'id' => $comment->uuid,
            'authorName' => $comment->is_anonymous ? __('Anonymous') : $comment->user->name,
            'isMine' => $comment->user_id === $this->user->id,
            'isAnonymous' => $comment->is_anonymous,
            'isPrivate' => $comment->is_private,
            'isHidden' => $comment->status === ChangelogCommentStatusEnum::Hidden,
            'body' => $comment->body,
            'createdAt' => $comment->created_at,
            'replies' => $comment->replies
                ->filter(fn (ChangelogCommentReply $reply): bool => $reply->is_public)
                ->map(fn (ChangelogCommentReply $reply): array => [
                    'authorName' => $reply->author_name,
                    'body' => $reply->body,
                    'createdAt' => $reply->created_at,
                ])
                ->values()
                ->all(),
        ])->values()->all();
    }

    /** @return Collection<int, ChangelogComment> */
    private function visibleComments(): Collection
    {
        return $this->comments ??= ChangelogComment::query()
            ->with(['user', 'replies'])
            ->where('entry_id', $this->entryId)
            ->visibleTo($this->user)
            ->orderBy('id')
            ->get();
    }
}
