<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountExportStatusEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\AccountExportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * One request to take the data of an account out of the application, as a file
 * the account holder downloads.
 *
 * Preparing the file takes time and happens on a queue, so the request, where it
 * stands and the file it produced have to outlive the page that asked for it. An
 * account prepares one export at a time.
 *
 * The file sits on `disk` at `path`, under the account prefix, and is reached
 * only through this row, never through a path built from user input. It does not
 * count against the storage quota: it is a copy of what is already counted. Seven
 * days after `ready_at` the file is deleted and the row stays, marked expired, so
 * the screen can say what happened.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $user_id
 * @property list<int> $vault_ids
 * @property AccountExportStatusEnum $status
 * @property string $disk
 * @property string $path
 * @property int|null $size_in_bytes
 * @property CarbonImmutable|null $ready_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read User $user
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'user_id',
    'vault_ids',
    'status',
    'disk',
    'path',
    'size_in_bytes',
    'ready_at',
    'expires_at',
])]
class AccountExport extends Model
{
    /** @use HasFactory<AccountExportFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'vault_ids' => 'array',
            'status' => AccountExportStatusEnum::class,
            'size_in_bytes' => 'integer',
            'ready_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the account the data comes from.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who asked for the export.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the folder holding the file of this export and whatever a run that did
     * not finish left beside it, under the account's prefix and named after the
     * export, so removing it removes exactly this export's files.
     */
    public function directory(): string
    {
        return "{$this->account->transfersDirectory()}/{$this->uuid}";
    }
}
