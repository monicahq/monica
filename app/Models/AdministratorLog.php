<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdministratorActionEnum;
use App\Exceptions\LogIsAppendOnly;
use Carbon\CarbonImmutable;
use Database\Factories\AdministratorLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;

/**
 * Class AdministratorLog
 *
 * One thing an administrator did in the admin space: who, what, to what, and
 * when. It is deliberately not {@see Log}. That one is the journal a user reads
 * about their own account, and an operator deciding something about an account
 * is not something the person on it did. Keeping the two in one table would put
 * one forgotten `where` between an operator and a user's screen.
 *
 * A row is written once and never touched again, which is the same rule and the
 * same exception as the user journal.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $user_email
 * @property string $action
 * @property array<string, mixed>|null $parameters
 * @property int|null $account_id
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $ip_address
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $user
 * @property-read Account|null $account
 * @property-read Model|null $subject
 * @property-read string $author
 * @property-read string $description
 */
#[Fillable([
    'user_id',
    'user_email',
    'action',
    'parameters',
    'account_id',
    'subject_type',
    'subject_id',
    'ip_address',
])]
class AdministratorLog extends Model
{
    /** @use HasFactory<AdministratorLogFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'parameters' => 'array',
        ];
    }

    /**
     * The record of what an operator did is worth nothing if an operator can
     * rewrite it. Only a retention policy may ever remove one, and there is
     * none yet.
     */
    #[Override]
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogIsAppendOnly('An administrator log entry cannot be changed once it is written.');
        });

        static::deleting(function (): never {
            throw new LogIsAppendOnly('An administrator log entry cannot be deleted.');
        });
    }

    /**
     * Get the administrator who took the action, as long as they still exist.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the account the action was taken on, when it was taken on one. A
     * decision about a marketplace submission is not.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get what the action was taken on, which may be an account, a user or a
     * version of a listing, and may no longer exist.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get which administrator took the action. The entry outlives them, so it
     * falls back to the address they had, and to a name for the instance itself
     * when the console acted.
     *
     * @return Attribute<string, never>
     */
    protected function author(): Attribute
    {
        return Attribute::make(
            // An administrator who was soft deleted leaves the relation empty
            // while the column still points at them, so the fallback has to
            // cover more than a null column.
            get: fn (): string => match (true) {
                $this->wasPerformedByTheConsole() => __('The console'),
                default => $this->user->name ?? $this->user_email,
            },
        );
    }

    /**
     * Get whether the action came from the command line rather than from
     * somebody signed in, which is how the first administrator is designated.
     */
    public function wasPerformedByTheConsole(): bool
    {
        return $this->user_email === Log::SYSTEM;
    }

    /**
     * Get what the action reads as, in the language of whoever is looking. An
     * action this version of the application no longer knows about is shown by
     * its raw name rather than hidden.
     *
     * @return Attribute<string, never>
     */
    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn (): string => __(
                AdministratorActionEnum::tryFrom($this->action)?->description() ?? $this->action,
                $this->parameters ?? [],
            ),
        );
    }
}
