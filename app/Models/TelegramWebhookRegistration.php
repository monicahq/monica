<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TelegramWebhookRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class TelegramWebhookRegistration
 *
 * The last time Telegram was told where this instance receives what people say
 * to the bot. There is at most one row: registering again rewrites it.
 *
 * It keeps a fingerprint of the token and of the secret that were in effect,
 * never the values, so the configuration screen can tell that a later change has
 * made the registration out of date without this table becoming a second place
 * a credential can be read from.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $url
 * @property string $token_fingerprint
 * @property string $secret_fingerprint
 * @property CarbonImmutable $registered_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $user
 */
#[Fillable([
    'user_id',
    'url',
    'token_fingerprint',
    'secret_fingerprint',
    'registered_at',
])]
class TelegramWebhookRegistration extends Model
{
    /** @use HasFactory<TelegramWebhookRegistrationFactory> */
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
            'registered_at' => 'datetime',
        ];
    }

    /**
     * The administrator who registered the bot, or nobody when the console did.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
