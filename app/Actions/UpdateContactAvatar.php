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
 * Put a picture on a contact, in place of the initials the product draws
 * otherwise.
 *
 * The picture is a {@see File} like any other upload, which is what makes the
 * quota, the sweep and the grace period apply to it: an avatar written as a
 * path on the contact would be bytes nothing could account for.
 *
 * A contact only ever has one avatar, so the file it pointed at before is let
 * go once the new one is in place.
 */
class UpdateContactAvatar
{
    /**
     * The mime types we accept. Anything else is rejected, whatever the
     * extension of the uploaded file claims.
     */
    private const array ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const int MAX_SIZE_IN_BYTES = 5 * 1024 * 1024;

    /**
     * Where the square crop waits between coming out of the image pipeline and
     * being taken in hand by {@see CreateFile}.
     */
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

    /**
     * The file is checked here as well as in the controller, on what it
     * actually is rather than on what its name claims.
     */
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

    /**
     * Crop the picture square and let {@see CreateFile} take it from there, so
     * the row the contact points at holds the avatar itself: the quota then
     * counts the bytes actually kept, and there is one file to serve rather
     * than an original nothing draws.
     *
     * The crop goes to a scratch path on the local disk on the way, because the
     * image pipeline writes to a disk and CreateFile takes an upload. It is
     * removed as soon as the file is where it belongs.
     */
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

        new AttachFile(user: $this->user, file: $this->file)->execute();
    }

    /**
     * The relationship is set as well as the column, so whoever the contact is
     * handed back to reads the picture that was just chosen rather than the one
     * loaded on the way in.
     */
    private function save(): void
    {
        $this->previous = $this->contact->avatar;

        $this->contact->avatar_file_id = $this->file->id;
        $this->contact->save();
        $this->contact->setRelation('avatar', $this->file);
    }

    /**
     * The file the contact pointed at before is let go rather than deleted.
     * Detaching stamps the moment nothing pointed at it any more, and the sweep
     * is the only thing that takes bytes off the disk.
     */
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
        )->onQueue('low');
    }
}
