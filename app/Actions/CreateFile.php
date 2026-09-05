<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\NotEnoughStorageLeft;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Put an uploaded file where it belongs and write down that it is there.
 */
class CreateFile
{
    private File $file;

    private string $path;

    public function __construct(
        private readonly User $user,
        private readonly UploadedFile $upload,
        private readonly ?Vault $vault = null,
    ) {}

    public function execute(): File
    {
        $this->validate();
        $this->store();
        $this->log();

        return $this->file;
    }

    private function validate(): void
    {
        if ($this->vault !== null && $this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        $account = $this->user->account;

        if (! $account->isMetered()) {
            return;
        }

        $wouldHold = $account->storage_used_in_bytes + (int) $this->upload->getSize();

        if ($wouldHold > $account->storage_limit_in_bytes) {
            throw new NotEnoughStorageLeft('This file would take the account past the storage it is allowed.');
        }
    }

    private function store(): void
    {
        $disk = (string) config('filesystems.default');
        $directory = File::directoryFor($this->user->account_id, $this->vault?->id);
        $name = Str::uuid()->toString();

        $this->path = $this->upload->storeAs($directory, $name, ['disk' => $disk]);

        $size = (int) $this->upload->getSize();
        $dimensions = $this->dimensions();

        DB::transaction(function () use ($disk, $size, $dimensions): void {
            $this->file = File::query()->create([
                'account_id' => $this->user->account_id,
                'vault_id' => $this->vault?->id,
                'disk' => $disk,
                'path' => $this->path,
                'original_name' => $this->upload->getClientOriginalName(),
                'mime_type' => (string) $this->upload->getMimeType(),
                'size_in_bytes' => $size,
                'checksum' => (string) hash_file('sha256', $this->upload->getRealPath()),
                'width' => $dimensions[0],
                'height' => $dimensions[1],
                'variants' => null,
                'reference_count' => 0,
                'orphaned_at' => now(),
                'uploaded_by_user_id' => $this->user->id,
            ]);

            Account::query()->whereKey($this->user->account_id)->increment('storage_used_in_bytes', $size);
        });
    }

    /** @return array{0: int|null, 1: int|null} */
    private function dimensions(): array
    {
        if (! str_starts_with((string) $this->upload->getMimeType(), 'image/')) {
            return [null, null];
        }

        $size = @getimagesize((string) $this->upload->getRealPath());

        return $size === false ? [null, null] : [$size[0], $size[1]];
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::FileUpload,
            vault: $this->vault,
            parameters: ['name' => $this->file->original_name],
            loggable: $this->file,
        )->onQueue('low');
    }
}
