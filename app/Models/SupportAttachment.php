<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\SupportAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class SupportAttachment
 *
 * A file somebody sent with a support message: a screenshot, an invoice, a log.
 *
 * It is not a {@see File}. A file counts against the storage the account is allowed, and the
 * account most likely to need help is the one that has run out of it, so a screenshot sent to the
 * support team must never be refused for lack of space. These files live in a folder of their own
 * per account, which the deletion of the account removes in one go.
 *
 * The path is built here and never taken from a request, and what travels in a URL is the uuid.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $support_message_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size_in_bytes
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read SupportMessage $supportMessage
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'support_message_id',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size_in_bytes',
])]
class SupportAttachment extends Model
{
    /** @use HasFactory<SupportAttachmentFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;

    public const int MAXIMUM_PER_MESSAGE = 5;

    public const int MAXIMUM_SIZE_IN_KILOBYTES = 10240;

    public const string ACCEPTED_TYPES = 'jpg,jpeg,png,gif,webp,heic,pdf,txt,log';

    /**
     * Get the account of the user who sent it.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the message it was sent with.
     *
     * @return BelongsTo<SupportMessage, $this>
     */
    public function supportMessage(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class);
    }

    /**
     * The folder holding every support file of an account.
     */
    public static function directoryFor(int $accountId): string
    {
        return "support/{$accountId}";
    }
}
