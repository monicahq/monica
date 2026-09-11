<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationDeliveryModeEnum;
use Carbon\CarbonImmutable;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * How the account wants to be told things.
 *
 * Section 10.4 puts this at the account level and names exactly what it holds:
 * the channels that are on, the hours during which nothing may disturb, and
 * whether things arrive at once or folded into a daily summary. It is one row
 * rather than a handful of columns scattered over the account, because delivery
 * reads all of it together on every single send.
 *
 * It adds no second lead time. When somebody wants to be told is said by the
 * rule's moments, and each moment has already become a deadline at that exact
 * instant before delivery begins. Moving a notification earlier here would apply
 * the offset twice.
 *
 * Which channels are switched on is not here. That is one row per channel in
 * `notification_channels`, because a channel carries a destination, a
 * credential and a state of its own, and because adding a channel to the
 * product must not mean adding a column here.
 *
 * Turning every channel off is allowed. Somebody who wants silence should not
 * have to go and disable every rule they ever made to get it.
 *
 * @property int $id
 * @property int $account_id
 * @property bool $quiet_hours_are_enabled
 * @property string|null $quiet_hours_start
 * @property string|null $quiet_hours_end
 * @property NotificationDeliveryModeEnum $delivery_mode
 * @property string|null $digest_time
 * @property array<array-key, mixed>|null $digest_weekdays
 * @property string $timezone
 * @property array<array-key, mixed>|null $type_overrides
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 */
#[Fillable([
    'account_id',
    'quiet_hours_are_enabled',
    'quiet_hours_start',
    'quiet_hours_end',
    'delivery_mode',
    'digest_time',
    'digest_weekdays',
    'timezone',
    'type_overrides',
])]
class NotificationPreference extends Model
{
    /** @use HasFactory<NotificationPreferenceFactory> */
    use HasFactory;

    /**
     * The hour a daily summary goes out when nobody has said otherwise. Six in
     * the evening is when somebody reads a list of what is coming, rather than
     * the hour they are still working through the day.
     */
    public const string DEFAULT_DIGEST_TIME = '18:00';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'quiet_hours_are_enabled' => 'boolean',
            'delivery_mode' => NotificationDeliveryModeEnum::class,
            'digest_weekdays' => 'array',
            'type_overrides' => 'array',
        ];
    }

    /**
     * Get the account the preferences belong to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Whether everything is folded into one summary rather than arriving on its
     * own.
     */
    public function foldsIntoADigest(): bool
    {
        return $this->delivery_mode === NotificationDeliveryModeEnum::Digest;
    }
}
