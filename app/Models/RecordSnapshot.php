<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SnapshotReasonEnum;
use App\Exceptions\SnapshotIsImmutable;
use Carbon\Carbon;
use Database\Factories\RecordSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class RecordSnapshot
 *
 * The complete state of a contact or an entity at a moment, kept so it can be
 * restored. It exists against the log rather than instead of it: a snapshot is
 * there to go back, a log is there to explain who did what, and neither
 * replaces the other.
 *
 * The moment that matters most is a structure change. Moving a record to
 * another template version saves the previous state first, always, and that
 * guarantee is what makes changing structures safe enough to offer casually.
 *
 * The payload is self contained. Restoring has to work even if the structure it
 * came from was retired since, so it carries page keys and labels, section
 * placement, and field keys and labels, not only the values.
 *
 * @property int $id
 * @property int $account_id
 * @property int $vault_id
 * @property string $snapshotable_type
 * @property int $snapshotable_id
 * @property int $template_version_id
 * @property SnapshotReasonEnum $reason
 * @property array<array-key, mixed> $payload
 * @property array<array-key, mixed> $file_ids
 * @property int|null $created_by_user_id
 * @property Carbon|null $restored_at
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read Contact|Entity $snapshotable
 * @property-read TemplateVersion $templateVersion
 * @property-read User|null $createdBy
 */
class RecordSnapshot extends Model
{
    /** @use HasFactory<RecordSnapshotFactory> */
    use HasFactory;

    protected $table = 'record_snapshots';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'vault_id',
        'snapshotable_type',
        'snapshotable_id',
        'template_version_id',
        'reason',
        'payload',
        'file_ids',
        'created_by_user_id',
        'restored_at',
        'expires_at',
    ];

    /**
     * A snapshot is written and then left alone. The one thing that moves is
     * the note of when it was used to restore, which says something about the
     * snapshot rather than about the state captured in it.
     *
     * Deleting is refused for the reason a log entry is: only a retention rule
     * may ever remove one, and there is none yet.
     */
    protected static function booted(): void
    {
        static::updating(function (RecordSnapshot $snapshot): void {
            $changed = array_keys($snapshot->getDirty());

            if (array_diff($changed, ['restored_at', 'updated_at']) !== []) {
                throw new SnapshotIsImmutable('The state a snapshot captured cannot be rewritten.');
            }
        });

        static::deleting(function (): never {
            throw new SnapshotIsImmutable('A snapshot cannot be deleted.');
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => SnapshotReasonEnum::class,
            'payload' => 'array',
            'file_ids' => 'array',
            'restored_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the account the snapshot belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault the record lives in.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the record captured, which is a contact or an entity.
     *
     * @return MorphTo<Model, $this>
     */
    public function snapshotable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the structure the record was on at the time.
     *
     * @return BelongsTo<TemplateVersion, $this>
     */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(TemplateVersion::class);
    }

    /**
     * Get whoever the action that triggered it belonged to, which is null when
     * the product took it on its own.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Every file any live snapshot points at. The orphan sweep asks this before
     * it takes anything: the grace period exists precisely so that restoring an
     * older state recovers a file detached a moment ago, and a file a snapshot
     * still names is not swept whatever its reference count says.
     *
     * @return list<int>
     */
    public static function fileIdsInUse(): array
    {
        return self::query()
            ->select('file_ids')
            ->get()
            ->flatMap(fn (self $snapshot): array => $snapshot->file_ids)
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
