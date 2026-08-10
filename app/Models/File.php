<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\FileFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Class File
 *
 * An uploaded file: a photo, a document, a scan. Files are attached to records
 * through fields and to activities, and to nothing else.
 *
 * It is a model of its own rather than a path written into whatever points at
 * it, for three reasons. The product has to know at any moment which files are
 * really in use, or the quota it enforces is a guess. It has to be able to
 * sweep up what nothing points at any more. And that sweep has to wait long
 * enough that restoring an old version of a record recovers a file detached a
 * moment ago, which is impossible if detaching deletes.
 *
 * Nothing is ever served by the path a request hands over. The path is built
 * here from the account and the vault, and what travels in a URL is the uuid.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int|null $vault_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size_in_bytes
 * @property string $checksum
 * @property int|null $width
 * @property int|null $height
 * @property array<array-key, mixed>|null $variants
 * @property int $reference_count
 * @property Carbon|null $orphaned_at
 * @property int|null $uploaded_by_user_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Account $account
 * @property-read Vault|null $vault
 * @property-read User|null $uploadedBy
 */
class File extends Model
{
    /** @use HasFactory<FileFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $table = 'files';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'vault_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_in_bytes',
        'checksum',
        'width',
        'height',
        'variants',
        'reference_count',
        'orphaned_at',
        'uploaded_by_user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_in_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'variants' => 'array',
            'reference_count' => 'integer',
            'orphaned_at' => 'datetime',
        ];
    }

    /**
     * Get the columns that are given a unique identifier of their own. The
     * primary key stays an incrementing integer, and the uuid is what every
     * path and URL is built from.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Get the column a file is looked up by in a URL.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Where a file of this account, and of this vault, is written.
     *
     * The account comes first and the vault second, always, which is what every
     * download, preview, export and share link checks before it serves
     * anything. A file that belongs to the account rather than to one of its
     * compartments, such as a user photo, sits directly under the account.
     */
    public static function directoryFor(int $accountId, ?int $vaultId): string
    {
        $directory = 'accounts/'.$accountId;

        return $vaultId === null ? $directory : $directory.'/vaults/'.$vaultId;
    }

    /**
     * Whether nothing points at the file any more, which is what starts the
     * grace period before the sweep takes it.
     */
    public function isOrphan(): bool
    {
        return $this->reference_count === 0;
    }

    /**
     * The disk the file was written to, read from the row rather than from the
     * configuration: an instance that changed disk has to go on resolving what
     * it wrote before.
     */
    public function disk(): Filesystem
    {
        return Storage::disk($this->disk);
    }

    /**
     * Get the account the file belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault the file belongs to, which an account level file has none
     * of.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get whoever uploaded it, which an import or a module has none of.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
