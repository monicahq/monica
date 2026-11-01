<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Put a picture on a contact, in place of the initials the product draws otherwise.
 */
class UpdateContactAvatar
{
    private const array ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const int MAX_SIZE_IN_BYTES = 5 * 1024 * 1024;

    private const string SCRATCH_DIRECTORY = 'tmp/contact-avatars';

    private ?File $previous = null;

    private File $file;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly UploadedFile $upload,
    ) {}

    public function execute(): Contact
    {
        $this->validate();
        $this->store();
        $this->save();
        $this->releasePrevious();
        $this->log();

        return $this->contact;
    }

    private function validate(): void
    {
        if (! $this->contact->isReachableThrough($this->user, $this->vault)) {
            throw new ModelNotFoundException('This contact is not in that vault.');
        }

        if (! in_array($this->upload->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('The file must be a jpeg, png or webp image');
        }

        if ($this->upload->getSize() > self::MAX_SIZE_IN_BYTES) {
            throw new InvalidArgumentException('The file must not be larger than 5 MB');
        }
    }

    private function store(): void
    {
        $scratch = new ResizeImage(
            file: $this->upload,
            width: Contact::AVATAR_PIXELS,
            height: Contact::AVATAR_PIXELS,
            path: self::SCRATCH_DIRECTORY,
            name: Str::uuid()->toString().'.webp',
            disk: 'local',
        )->execute();

        $squared = new UploadedFile(
            path: Storage::disk('local')->path($scratch),
            originalName: pathinfo($this->upload->getClientOriginalName(), PATHINFO_FILENAME).'.webp',
            mimeType: 'image/webp',
            test: true,
        );

        $this->file = new CreateFile(
            user: $this->user,
            upload: $squared,
            vault: $this->vault,
        )->execute();

        Storage::disk('local')->delete($scratch);

        $this->storeThumbnail();

        new AttachFile(user: $this->user, file: $this->file)->execute();
    }

    private function storeThumbnail(): void
    {
        $pixels = Contact::AVATAR_THUMBNAIL_PIXELS;
        $suffix = "_{$pixels}";

        new ResizeImage(
            file: $this->upload,
            width: $pixels,
            height: $pixels,
            path: dirname($this->file->path),
            name: basename($this->file->path).$suffix,
            disk: $this->file->disk,
        )->execute();

        $this->file->variants = [['suffix' => $suffix, 'pixels' => $pixels]];
        $this->file->save();
    }

    private function save(): void
    {
        $this->previous = $this->contact->avatar;

        $this->contact->avatar_file_id = $this->file->id;
        $this->contact->save();
        $this->contact->setRelation('avatar', $this->file);
    }

    private function releasePrevious(): void
    {
        if ($this->previous === null) {
            return;
        }

        new DetachFile(user: $this->user, file: $this->previous)->execute();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ContactAvatarUpdate,
            vault: $this->vault,
            parameters: ['name' => $this->contact->name],
            loggable: $this->contact,
        );
    }
}
