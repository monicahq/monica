<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Keep an uploaded file with a group: a team photo, a schedule, a registration form. The file counts
 * against the account's storage like every other upload, and the group holds one reference on it.
 */
class AddGroupFile
{
    private GroupFile $attachment;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Group $group,
        private readonly UploadedFile $upload,
    ) {}

    public function execute(): GroupFile
    {
        $this->validate();

        $file = new CreateFile(user: $this->user, upload: $this->upload, vault: $this->vault)->execute();

        $this->attach($file);
        $this->log($file);

        return $this->attachment;
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->group->account_id !== $this->user->account_id || $this->group->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This group is in another vault.');
        }
    }

    private function attach(File $file): void
    {
        DB::transaction(function () use ($file): void {
            $this->attachment = GroupFile::query()->create([
                'account_id' => $this->user->account_id,
                'group_id' => $this->group->id,
                'file_id' => $file->id,
                'added_by_user_id' => $this->user->id,
            ]);

            new AttachFile(user: $this->user, file: $file)->execute();
        });
    }

    private function log(File $file): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::GroupFileAddition,
            vault: $this->vault,
            parameters: ['file' => $file->original_name, 'name' => $this->group->name],
            loggable: $this->group,
        );
    }
}
