<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SupportCategoryEnum;
use App\Enums\SupportMessageKindEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\SupportRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Class SupportRequest
 *
 * Something a person asked the administrators of the instance: a question, a bug, an idea, a billing
 * problem. It replaces writing an email, so the question and every answer to it live in one thread
 * both sides can read.
 *
 * It belongs to the account, never to a vault, because it is about the product rather than about the
 * people somebody keeps in it. The administrators who answer it see the thread and a few counts about
 * the account, and nothing from inside a vault.
 *
 * The number people quote is random rather than the primary key, so a number never says how many
 * requests the instance has received. The thread carries the user's messages, the administrators'
 * replies and their internal notes; a note is for administrators only, which is why every user
 * surface reads the thread through `visibleMessages()`.
 *
 * @property int $id
 * @property string $uuid
 * @property int $number
 * @property int $account_id
 * @property int|null $user_id
 * @property SupportCategoryEnum $category
 * @property string $subject
 * @property SupportRequestStatusEnum $status
 * @property string $application_version
 * @property string $locale
 * @property CarbonImmutable $last_activity_at
 * @property CarbonImmutable|null $solved_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read string $reference
 * @property-read Account $account
 * @property-read User|null $user
 * @property-read Collection<int, SupportMessage> $messages
 * @property-read Collection<int, SupportMessage> $visibleMessages
 * @property-read Collection<int, SupportStatusChange> $statusChanges
 */
#[RouteKey('uuid')]
#[Fillable([
    'number',
    'account_id',
    'user_id',
    'category',
    'subject',
    'status',
    'application_version',
    'locale',
    'last_activity_at',
    'solved_at',
])]
class SupportRequest extends Model
{
    /** @use HasFactory<SupportRequestFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;

    /**
     * Get the account the request belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who opened it.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the whole thread, internal notes included, oldest first.
     *
     * @return HasMany<SupportMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Get the thread the user may read: everything but the internal notes, oldest first.
     *
     * @return HasMany<SupportMessage, $this>
     */
    public function visibleMessages(): HasMany
    {
        return $this->messages()->where('kind', '!=', SupportMessageKindEnum::Note->value);
    }

    /**
     * Get every change of status, oldest first.
     *
     * @return HasMany<SupportStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(SupportStatusChange::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Get the number as people quote it.
     *
     * @return Attribute<string, never>
     */
    protected function reference(): Attribute
    {
        return Attribute::make(
            get: fn (): string => "MON-{$this->number}",
        );
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'category' => SupportCategoryEnum::class,
            'status' => SupportRequestStatusEnum::class,
            'last_activity_at' => 'datetime',
            'solved_at' => 'datetime',
        ];
    }
}
