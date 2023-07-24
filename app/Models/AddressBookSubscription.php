<?php

namespace App\Models;

use App\Domains\Contact\DavClient\Services\Utils\Dav\DavClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddressBookSubscription extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'addressbook_subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'vault_id',
        'uri',
        'capabilities',
        'username',
        'password',
        'readonly',
        'syncToken',
        'frequency',
        'last_synchronized_at',
        'active',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'string',
        'vault_id' => 'string',
        'last_synchronized_at' => 'datetime',
        'readonly' => 'boolean',
        'active' => 'boolean',
        'capabilities' => 'array',
    ];

    /**
     * Eager load account with every contact.
     *
     * @var array<string>
     */
    protected $with = [
        'user',
    ];

    /**
     * Get the account record associated with the subscription.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user record associated with the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vault record associated with the subscription.
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the local synctoken.
     */
    public function localSyncToken(): BelongsTo
    {
        return $this->belongsTo(SyncToken::class);
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
