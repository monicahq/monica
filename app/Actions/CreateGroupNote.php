<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\NoteIsEmpty;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\GroupNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Write something down about a group as a whole. The note stays on the group and never reaches the
 * page of anybody in it.
 */
class CreateGroupNote
{
    private string $text;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
        private readonly string $body,
    ) {}

    public function execute(): GroupNote
    {
        $this->sanitize();
        $this->validate();

        $note = GroupNote::query()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'group_id' => $this->group->id,
            'body' => $this->text,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->log($note);

        return $note;
    }

    private function sanitize(): void
    {
        $this->text = TextSanitizer::plainText($this->body);
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->group->account_id !== $this->user->account_id || $this->group->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This group is in another vault.');
        }

        if ($this->text === '') {
            throw new NoteIsEmpty(__('Write something about the group.'));
        }
    }

    private function log(GroupNote $note): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::GroupNoteCreation,
            vault: $this->vault,
            parameters: ['name' => $this->group->name],
            loggable: $note,
        );
    }
}
