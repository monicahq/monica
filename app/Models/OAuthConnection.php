<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\OAuthConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Client;
use Override;

/**
 * One app connected to an account through OAuth, whatever the app is: a phone, an assistant or
 * anything else that signed in. It lasts from the exchange of its authorization code until the app
 * signs out, is disconnected from the web, or goes unused past the life of its refresh token.
 *
 * Passport gives every renewal a new token row, so this row is what stays the same for one
 * connection. It keeps fingerprints of the code and the tokens, never the values. A connection
 * without a code fingerprint is an earlier one: an assistant connected before connections were
 * recorded, which stands for every token that assistant holds for the person.
 *
 * @property int $id
 * @property int $user_id
 * @property string $client_id
 * @property string|null $label
 * @property array<int, string>|null $scopes
 * @property string|null $access_token_id
 * @property string|null $refresh_token_hash
 * @property string|null $previous_refresh_token_hash
 * @property string|null $authorization_code_hash
 * @property CarbonImmutable $last_used_at
 * @property CarbonImmutable|null $ended_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Client $client
 * @property-read string $name
 */
#[Fillable([
    'user_id',
    'client_id',
    'label',
    'scopes',
    'access_token_id',
    'refresh_token_hash',
    'previous_refresh_token_hash',
    'authorization_code_hash',
    'last_used_at',
    'ended_at',
])]
#[Table('oauth_connections')]
class OAuthConnection extends Model
{
    /** @use HasFactory<OAuthConnectionFactory> */
    use HasFactory;

    use MassPrunable;

    /**
     * The id of the OAuth client the Monica mobile apps sign in through: one public client, without
     * a secret, which a migration creates in the database of every instance, so nobody running Monica
     * has anything to set up.
     *
     * The value is an arbitrary UUID, chosen once and the same on every instance. It is not a secret
     * and protects nothing: it travels in clear in every sign-in address and `/api/v1/status` hands it
     * out. Sign-in is protected by PKCE, the exact return addresses below and the consent page. The
     * apps never hard-code it, they read it from the status. Being fixed lets the code tell the mobile
     * client apart for the one rule that depends on the client: which scope it may ask for.
     *
     * It MUST never change. Tokens, authorization codes and connections point at this id, so a new
     * value would sign every phone out and leave the migrations that wrote it out pointing at the old
     * one.
     */
    public const string MOBILE_CLIENT_ID = '0198f6a4-7c2e-7d3b-9a61-5e0b4c8d2f17';

    public const string ANDROID_REDIRECT_URI = 'com.monicahq.android:/oauth/callback';

    public const string IOS_REDIRECT_URI = 'com.monicahq.ios:/oauth/callback';

    /** The return addresses of the mobile client, one per app. */
    public const array MOBILE_REDIRECT_URIS = [self::ANDROID_REDIRECT_URI, self::IOS_REDIRECT_URI];

    public const string API_SCOPE = 'api';

    public const int LIFETIME_IN_DAYS = 30;

    /**
     * How long after a renewal the refresh token it replaced may come back without ending the connection:
     * two requests of the app sent at once present the same token (RFC 9700, section 4.14.2).
     */
    public const int REPLAY_GRACE_IN_SECONDS = 30;

    /**
     * The client a token request names, read the way the OAuth server reads it: from the body, or else
     * from HTTP Basic authentication.
     */
    public static function clientIdOf(Request $request): string
    {
        return $request->string('client_id')->toString() ?: (string) $request->getUser();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'last_used_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Get the user the app is connected to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the OAuth client of the connected app.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The name the person recognises the connection by: the one the app gave itself, or else the
     * name of its client.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->label ?? $this->client->name,
        );
    }

    /**
     * Keep the connections still running: not ended, and renewed within the life of a refresh token.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('ended_at')
            ->where('last_used_at', '>', now()->subDays(self::LIFETIME_IN_DAYS));
    }

    /**
     * The connections nobody can use again: ended, or unused past the life of a refresh token.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->whereNotNull('ended_at')
            ->orWhere('last_used_at', '<=', now()->subDays(self::LIFETIME_IN_DAYS));
    }

    /**
     * Resolve a connection named in a URL, and only ever one of your own. A connection somebody does
     * not own fails as though it did not exist.
     */
    #[Override]
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('user_id', Auth::id())
            ->first();
    }
}
