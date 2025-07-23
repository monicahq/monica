<?php

namespace App\Models;

use App\Domains\Contact\DavClient\Services\Utils\Dav\DavClient;
use App\Logging\Loggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AddressBookSubscription extends Model implements Loggable
{
    use HasFactory, HasUuids;

    /** Allow push changes to the distant server */
    public const WAY_PUSH = 0x1;

    /** Allow get changes from the distant server */
    public const WAY_GET = 0x2;

    /** Allow both push and get changes from the distant server */
    public const WAY_BOTH = self::WAY_GET | self::WAY_PUSH;

    protected $table = 'addressbook_subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'vault_id',
        'uri',
        'capabilities',
        'username',
        'password',
        'sync_way',
        'distant_sync_token',
        'current_logid',
        'frequency',
        'last_synchronized_at',
        'active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'user_id' => 'string',
        'vault_id' => 'string',
        'last_synchronized_at' => 'datetime',
        'active' => 'boolean',
        'capabilities' => 'array',
        'current_logid' => 'integer',
    ];

    /**
     * Eager load account with every contact.
     *
     * @var list<string>
     */
    protected $with = [
        'user',
    ];

    /**
     * Get the account record associated with the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user record associated with the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vault record associated with the subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the local synctoken.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\SyncToken, $this>
     */
    public function localSyncToken(): BelongsTo
    {
        return $this->belongsTo(SyncToken::class);
    }

    /**
     * Get the subscription's logs.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\App\Models\Log, $this>
     */
    public function logs(): MorphMany
    {
        return $this->morphMany(Log::class, 'loggable');
    }

    /**
     * Get password.
     *
     * @return Attribute<string,string>
     */
    public function password(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => decrypt($value, true),
            set: fn (string $value) => encrypt($value)
        );
    }

    /**
     * Get synchronization way.
     *
     * @return Attribute<bool,never>
     */
    public function isWayPush(): Attribute
    {
        return Attribute::make(
            get: fn (?bool $value, array $attributes) => ($attributes['sync_way'] & self::WAY_PUSH) === self::WAY_PUSH,
        );
    }

    /**
     * Get synchronization way.
     *
     * @return Attribute<bool,never>
     */
    public function isWayGet(): Attribute
    {
        return Attribute::make(
            get: fn (?bool $value, array $attributes) => ($attributes['sync_way'] & self::WAY_GET) === self::WAY_GET,
        );
    }

    /**
     * Get synchronization way.
     *
     * @return Attribute<bool,never>
     */
    public function isWayBoth(): Attribute
    {
        return Attribute::make(
            get: fn (?bool $value, array $attributes) => ($attributes['syncWay'] & self::WAY_BOTH) === self::WAY_BOTH,
        );
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', 1);
    }

    /**
     * Get a new client.
     */
    public function getClient(): DavClient
    {
        return app(DavClient::class)
            ->setBaseUri($this->uri)
            ->setCredentials($this->username, $this->password);
    }
}
