<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Enums\ChangelogCommentStatusEnum;
use App\Helpers\ChangelogEntries;
use App\Helpers\TextSanitizer;
use App\Jobs\LogAdministratorAction;
use App\Models\ChangelogComment;
use App\Models\ChangelogCommentReply;
use App\Models\User;
use App\Notifications\ChangelogReplySent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Answer a comment on What's new. The answer to a public comment is shown under it, signed; the
 * answer to a private one is emailed to its author and shown to nobody else. Answering a comment
 * also reviews it.
 */
class ReplyToChangelogComment
{
    private ChangelogCommentReply $reply;

    private string $cleanBody;

    public function __construct(
        private readonly User $administrator,
        private readonly ChangelogComment $comment,
        private readonly string $body,
    ) {}

    public function execute(): ChangelogCommentReply
    {
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->log();
        $this->notify();

        return $this->reply;
    }

    private function sanitize(): void
    {
        $this->cleanBody = TextSanitizer::plainText($this->body);
    }

    private function validate(): void
    {
        if (! $this->administrator->isAdministrator()) {
            throw new ModelNotFoundException('This comment does not exist.');
        }

        if ($this->cleanBody === '') {
            throw new InvalidArgumentException('A reply cannot be empty.');
        }
    }

    private function create(): void
    {
        DB::transaction(function (): void {
            $this->reply = ChangelogCommentReply::query()->create([
                'changelog_comment_id' => $this->comment->id,
                'author_user_id' => $this->administrator->id,
                'author_name' => $this->administrator->name,
                'body' => $this->cleanBody,
                'is_public' => ! $this->comment->is_private,
            ]);

            if ($this->comment->status === ChangelogCommentStatusEnum::New) {
                $this->comment->status = ChangelogCommentStatusEnum::Reviewed;
            }

            $this->comment->reviewed_at = now();
            $this->comment->save();
        });
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::ChangelogCommentReplied,
            account: $this->comment->user->account,
            parameters: ['name' => $this->comment->user->name, 'title' => $this->entryTitle()],
            subject: $this->comment,
        )->onQueue('low');
    }

    private function notify(): void
    {
        if (! $this->comment->is_private) {
            return;
        }

        $this->comment->user->notify(new ChangelogReplySent(
            entryTitle: $this->entryTitle(),
            administratorName: $this->administrator->name,
            body: $this->cleanBody,
        ));
    }

    private function entryTitle(): string
    {
        return app(ChangelogEntries::class)->find($this->comment->entry_id)->title ?? $this->comment->entry_id;
    }
}
