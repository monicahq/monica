<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannelEnum;
use App\Helpers\FlagState;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\NotificationChannelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * One way an account has said things may reach it, and everything that way
 * needs before it can carry anything.
 *
 * There is one row per account per channel, which is what lets a channel be
 * added to the product without a migration. Three boolean columns on the
 * preferences used to say the same thing for three channels, and a fourth
 * channel would have needed a fourth column.
 *
 * What a channel needs is a fact about the channel rather than about the row,
 * so `NotificationChannelEnum` is asked rather than a column read. In app and
 * email need nothing, because the row and the address are already there. A
 * text message needs a number. Telegram needs a conversation, which it hands
 * us itself rather than being told.
 *
 * No channel asks the account for a credential. In app and email need none,
 * and the provider account behind Telegram and the text message belongs to the
 * instance, configured once by whoever runs the server.
 *
 * A wrong phone number is accepted by a provider without complaint, so a
 * channel that fails silently is not switched on until a code sent to the
 * destination has been typed back. The code itself is only ever compared, so it
 * is hashed rather than encrypted, and it is kept here rather than in a table
 * that would hold at most one live row per channel.
 *
 * `verification_token_hash` is the other half of the same idea, for a channel
 * that hands us its own destination. Telegram is told a token when the account
 * opens a conversation with the bot and quotes it back through the webhook,
 * which is how one conversation is matched to one account. Only its hash is
 * kept, the way a magic link keeps only the hash of its own token, so a copy of
 * the table is not a set of working links. The hash is deterministic so it can
 * be looked up, and unique and short lived so it cannot be reused.
 *
 * `consecutive_failures` counts refusals in a row and any success resets it.
 * Past the threshold the product switches the channel off itself and writes
 * `disabled_reason`, so a channel that stopped working says so rather than
 * going quiet.
 *
 * @property int $id
 * @property int $account_id
 * @property NotificationChannelEnum $channel
 * @property bool $is_enabled
 * @property string|null $destination
 * @property string|null $verification_token_hash
 * @property CarbonImmutable|null $proven_at
 * @property string|null $proof_code_hash
 * @property CarbonImmutable|null $proof_expires_at
 * @property int $proof_attempts
 * @property int $proof_sent_count
 * @property CarbonImmutable|null $proof_last_sent_at
 * @property int $consecutive_failures
 * @property string|null $disabled_reason
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 */
#[RouteKey('channel')]
#[Fillable([
    'account_id',
    'channel',
    'is_enabled',
    'destination',
    'verification_token_hash',
    'proven_at',
    'proof_code_hash',
    'proof_expires_at',
    'proof_attempts',
    'proof_sent_count',
    'proof_last_sent_at',
    'consecutive_failures',
    'disabled_reason',
])]
class NotificationChannel extends Model
{
    /** @use HasFactory<NotificationChannelFactory> */
    use HasFactory;

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
            'channel' => NotificationChannelEnum::class,
            'is_enabled' => 'boolean',
            'proven_at' => 'immutable_datetime',
            'proof_expires_at' => 'immutable_datetime',
            'proof_attempts' => 'integer',
            'proof_sent_count' => 'integer',
            'proof_last_sent_at' => 'immutable_datetime',
            'consecutive_failures' => 'integer',
        ];
    }

    /**
     * Get the account the channel belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Whether everything the channel asks for has been supplied.
     */
    public function isSetUp(): bool
    {
        return ! $this->channel->needsDestination()
            || ($this->destination !== null && $this->destination !== '');
    }

    /**
     * Whether the account has asked to connect and nothing has come back yet.
     */
    public function isAwaitingConnection(): bool
    {
        return $this->verification_token_hash !== null
            && ($this->proof_expires_at === null || $this->proof_expires_at->isFuture());
    }

    /**
     * Whether the destination has been shown to be the account's own. A channel
     * that refuses a wrong destination loudly proves itself by working, so it
     * has nothing to prove here.
     */
    public function isProven(): bool
    {
        return ! $this->channel->provesSilently() || $this->proven_at !== null;
    }

    /**
     * Whether the channel may be switched on: something this server can
     * deliver, set up, and proven.
     *
     * A server with no text message provider configured is the reason for the
     * first one. Without it the channel would accept being switched on, write
     * notifications nobody could send, and disable itself a few failures later
     * for something the account could do nothing about.
     */
    public function isReady(): bool
    {
        return $this->channel->isConfiguredOnTheInstance()
            && $this->isSetUp()
            && $this->isProven();
    }

    /**
     * Whether the account may use this channel at all. Text messages are
     * withdrawn while the `sms-notifications` flag is off for the account; the
     * row and its number are kept, ready for the day it comes back on.
     */
    public function isOffered(): bool
    {
        if ($this->channel !== NotificationChannelEnum::Sms) {
            return true;
        }

        return FlagState::isOn('sms-notifications', $this->account);
    }

    /**
     * Whether a notification should be written for this channel at all.
     */
    public function mayCarry(): bool
    {
        return $this->is_enabled && $this->isOffered() && $this->isReady();
    }

    /**
     * A channel the account is not offered is not found, so every route naming
     * it answers 404, on the web and on the API alike.
     */
    protected function isReachableThroughAnAddress(): bool
    {
        return $this->isOffered();
    }

    /**
     * Forget the refusals counted so far. Any success clears them, so a channel
     * that fails now and then is never switched off for it: only one that
     * refuses everything for a run of sends is.
     */
    public function clearFailures(): void
    {
        if ($this->consecutive_failures === 0) {
            return;
        }

        $this->consecutive_failures = 0;
        $this->save();
    }
}
