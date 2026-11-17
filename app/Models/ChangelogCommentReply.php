<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ChangelogCommentReplyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class ChangelogCommentReply
 *
 * An administrator's answer to a comment on What's new. A public reply is shown under the comment;
 * a private one is emailed to its author and shown to nobody else. The author's name is written
 * down with the reply, so it stays signed after the administrator has gone.
 *
 * @property int $id
 * @property int $changelog_comment_id
 * @property int|null $author_user_id
 * @property string $author_name
 * @property string $body
 * @property bool $is_public
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ChangelogComment $comment
 * @property-read User|null $author
 */
#[Fillable([
    'changelog_comment_id',
    'author_user_id',
    'author_name',
    'body',
    'is_public',
])]
class ChangelogCommentReply extends Model
{
    /** @use HasFactory<ChangelogCommentReplyFactory> */
    use HasFactory;

    /**
     * Get the comment it answers.
     *
     * @return BelongsTo<ChangelogComment, $this>
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(ChangelogComment::class, 'changelog_comment_id');
    }

    /**
     * Get the administrator who wrote it, while that user exists.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }
}
