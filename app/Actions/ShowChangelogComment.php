<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Enums\ChangelogCommentStatusEnum;
use App\Helpers\ChangelogEntries;
use App\Jobs\LogAdministratorAction;
use App\Models\ChangelogComment;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Put a hidden comment back on What's new.
 */
class ShowChangelogComment
{
    public function __construct(
        private readonly User $administrator,
        private readonly ChangelogComment $comment,
    ) {}

    public function execute(): ChangelogComment
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->comment;
    }

    private function validate(): void
    {
        if (! $this->administrator->isAdministrator()) {
            throw new ModelNotFoundException('This comment does not exist.');
        }
    }

    private function update(): void
    {
        $this->comment->status = ChangelogCommentStatusEnum::Reviewed;
        $this->comment->save();
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::ChangelogCommentShown,
            account: $this->comment->user->account,
            parameters: ['name' => $this->comment->user->name, 'title' => app(ChangelogEntries::class)->find($this->comment->entry_id)->title ?? $this->comment->entry_id],
            subject: $this->comment,
        )->onQueue('low');
    }
}
