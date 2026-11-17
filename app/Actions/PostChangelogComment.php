<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ChangelogCommentStatusEnum;
use App\Helpers\ChangelogEntries;
use App\Helpers\ChangelogEntry;
use App\Helpers\TextSanitizer;
use App\Models\ChangelogComment;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Write a comment on a change of What's new. A public comment is shown under the change, under the
 * author's name or as Anonymous; a private one reaches the administrators only, so it is never
 * anonymous. Every comment waits for an administrator to review it.
 */
class PostChangelogComment
{
    public const int MAXIMUM_LENGTH = 2000;

    private string $cleanBody;

    public function __construct(
        private readonly User $user,
        private readonly string $entryId,
        private readonly string $body,
        private readonly bool $isPrivate,
        private readonly bool $isAnonymous,
    ) {}

    public function execute(): ChangelogComment
    {
        $this->sanitize();
        $this->validate();

        return ChangelogComment::query()->create([
            'user_id' => $this->user->id,
            'entry_id' => $this->entryId,
            'body' => $this->cleanBody,
            'is_private' => $this->isPrivate,
            'is_anonymous' => $this->isAnonymous && ! $this->isPrivate,
            'status' => ChangelogCommentStatusEnum::New,
        ]);
    }

    private function sanitize(): void
    {
        $this->cleanBody = TextSanitizer::plainText($this->body);
    }

    private function validate(): void
    {
        if (! app(ChangelogEntries::class)->find($this->entryId) instanceof ChangelogEntry) {
            throw new ModelNotFoundException('This change does not exist.');
        }

        if ($this->cleanBody === '' || mb_strlen($this->cleanBody) > self::MAXIMUM_LENGTH) {
            throw new InvalidArgumentException('A comment holds between 1 and 2000 characters.');
        }
    }
}
