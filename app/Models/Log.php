<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LogSourceEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\LogIsAppendOnly;
use Carbon\CarbonImmutable;
use Database\Factories\LogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;

/**
 * Class Log
 *
 * An action performed in an account: who did it, what to, when, from which
 * value and through which interface. It exists to explain, which is a different
 * job from going back to a former state.
 *
 * A row is written once and never touched again, so the model refuses to update
 * or delete one. Whatever it describes may be gone: the author is remembered by
 * the address they had, and the object acted on carries no foreign key.
 *
 * The `changes` column reads `['field' => ['before' => x, 'after' => y]]`. A
 * field the user marked sensitive records that it changed and not what to,
 * until the specification settles how those values may be kept.
 *
 * Careful with that column from inside this class: Eloquent keeps its own
 * protected `$changes` for dirty tracking, so `$this->changes` is not the
 * column. Reach for it with `$this->getAttribute('changes')`.
 *
 * @property int $id
 * @property int $account_id
 * @property int|null $vault_id
 * @property int|null $user_id
 * @property string $user_email
 * @property string $action
 * @property array<string, mixed>|null $parameters
 * @property string|null $loggable_type
 * @property int|null $loggable_id
 * @property array<string, mixed>|null $changes
 * @property LogSourceEnum $source
 * @property string|null $source_detail
 * @property string|null $ip_address
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $user
 * @property-read Vault|null $vault
 * @property-read Model|null $loggable
 * @property-read string $author
 * @property-read string $description
 */
#[Fillable([
    'account_id',
    'vault_id',
    'user_id',
    'user_email',
    'action',
    'parameters',
    'loggable_type',
    'loggable_id',
    'changes',
    'source',
    'source_detail',
    'ip_address',
])]
class Log extends Model
{
    /** @use HasFactory<LogFactory> */
    use HasFactory;

    /**
     * What stands in the place of an email address when nobody was behind the
     * action, such as a webhook subscription the application switched off after
     * failing to reach it.
     */
    public const string SYSTEM = 'system';

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
            'changes' => 'array',
            'source' => LogSourceEnum::class,
        ];
    }

    /**
     * A log is written and then left alone. It is the record of what happened,
     * and a record that can be rewritten is worth nothing. Only a retention
     * policy may ever remove one, and there is none yet.
     */
    #[Override]
    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogIsAppendOnly('A log entry cannot be changed once it is written.');
        });

        static::deleting(function (): never {
            throw new LogIsAppendOnly('A log entry cannot be deleted.');
        });
    }

    /**
     * Get the account the action was performed in.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault the action touched, if it was performed inside one.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the user who performed the action, as long as they still exist.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the object the action was performed on, which may be anything the
     * application holds, and may no longer exist.
     *
     * @return MorphTo<Model, $this>
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get who performed the action. A log outlives the user behind it, so it
     * falls back to the email address recorded at the time once that user is
     * gone, and to a name for the application itself when nobody was behind it.
     *
     * @return Attribute<string, never>
     */
    protected function author(): Attribute
    {
        return Attribute::make(
            // A user who was soft deleted leaves the relation empty while the
            // column still points at them, so the fallback has to cover more
            // than a null column.
            get: fn (): string => match (true) {
                $this->wasPerformedBySystem() => __('The system'),
                default => $this->user->name ?? $this->user_email,
            },
        );
    }

    /**
     * Get whether the application performed the action on its own, rather than
     * somebody asking it to.
     */
    public function wasPerformedBySystem(): bool
    {
        return $this->user_email === self::SYSTEM;
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
            get: function (): string {
                $sentence = UserActionEnum::tryFrom($this->action)?->description() ?? $this->action;
                $parameters = $this->parameters ?? [];

                // A sentence that shows a number carries a wording per plural
                // category, and only trans_choice picks between them.
                if (! str_contains($sentence, '|')) {
                    return __($sentence, $parameters);
                }

                return trans_choice($sentence, (int) ($parameters['count'] ?? 0), $parameters);
            },
        );
    }
}
