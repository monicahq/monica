<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Store a file sent with a support message in the support folder of its account, outside the
 * storage the account is allowed, and write down that it is there. Called inside the transaction
 * that writes the message, so a rollback takes the file off the disk again.
 */
class SupportAttachmentWriter
{
    public static function store(SupportMessage $message, int $accountId, UploadedFile $upload): SupportAttachment
    {
        $disk = (string) config('filesystems.default');
        $path = $upload->storeAs(SupportAttachment::directoryFor($accountId), Str::uuid()->toString(), ['disk' => $disk]);

        DB::afterRollBack(fn (): bool => Storage::disk($disk)->delete($path));

        return SupportAttachment::query()->create([
            'account_id' => $accountId,
            'support_message_id' => $message->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $upload->getClientOriginalName(),
            'mime_type' => (string) $upload->getMimeType(),
            'size_in_bytes' => (int) $upload->getSize(),
        ]);
    }
}
