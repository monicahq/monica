<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmailDeliveryStatusEnum;
use Carbon\CarbonImmutable;
use Database\Factories\EmailSentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class EmailSent
 *
 * Represents an email that has been sent in the system.
 *
 * @property int $id
 * @property int $account_id
 * @property int|null $user_id
 * @property string|null $uuid
 * @property string $email_type
 * @property string $email_address
 * @property string|null $subject
 * @property string|null $body
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $delivered_at
 * @property CarbonImmutable|null $bounced_at
 * @property CarbonImmutable|null $complained_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read EmailDeliveryStatusEnum $delivery_status
 */
#[Table('emails_sent')]
#[Fillable([
    'account_id',
    'user_id',
    'uuid',
    'email_type',
    'email_address',
    'subject',
    'body',
    'sent_at',
    'delivered_at',
    'bounced_at',
    'complained_at',
])]
class EmailSent extends Model
{
    /** @use HasFactory<EmailSentFactory> */
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
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'bounced_at' => 'datetime',
            'complained_at' => 'datetime',
        ];
    }

    /**
     * Where the email got to, read off the four timestamps.
     *
     * The reading is ordered rather than exclusive, because a provider can
     * report several of these facts, out of order, and the record keeps them
     * all. A complaint outranks a bounce because the recipient saying they did
     * not want the email is the stronger statement, and either outranks a
     * delivery, so an email that reached nobody never reads as delivered.
     *
     * @return Attribute<EmailDeliveryStatusEnum, never>
     */
    protected function deliveryStatus(): Attribute
    {
        return Attribute::make(
            get: fn (): EmailDeliveryStatusEnum => match (true) {
                $this->complained_at !== null => EmailDeliveryStatusEnum::Complained,
                $this->bounced_at !== null => EmailDeliveryStatusEnum::Bounced,
                $this->delivered_at !== null => EmailDeliveryStatusEnum::Delivered,
                default => EmailDeliveryStatusEnum::Sent,
            },
        );
    }

    /**
     * Get the account the email was sent on behalf of.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who received the email, when they have an account.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
