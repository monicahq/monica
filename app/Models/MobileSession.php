<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MobileSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Client;
use Override;

/**
 * One phone signed in to Monica through a mobile app, from its sign-in until it signs out, is
 * ended from the web, or goes unused past the life of its refresh token.
 *
 * Passport gives every renewal a new token row, so this row is what stays the same for one phone.
 * It keeps fingerprints of the code and the tokens, never the values.
 *
 * @property int $id
 * @property int $user_id
 * @property string $client_id
 * @property string|null $device_name
 * @property string $access_token_id
 * @property string $refresh_token_hash
 * @property string|null $previous_refresh_token_hash
 * @property string $authorization_code_hash
 * @property CarbonImmutable $last_used_at
 * @property CarbonImmutable|null $ended_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read Client $client
 */
#[Fillable([
    'user_id',
    'client_id',
    'device_name',
    'access_token_id',
    'refresh_token_hash',
    'previous_refresh_token_hash',
    'authorization_code_hash',
    'last_used_at',
    'ended_at',
])]
class MobileSession extends Model
{
    /** @use HasFactory<MobileSessionFactory> */
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
     * apps never hard-code it, they read it from the status. Being fixed lets the code recognise the
     * mobile client by comparing with this constant.
     *
     * It MUST never change. Tokens, authorization codes and mobile sessions point at this id, so a new
     * value would sign every phone out and leave the migrations that wrote it out pointing at the old
     * one.
     */
    public const string CLIENT_ID = '0198f6a4-7c2e-7d3b-9a61-5e0b4c8d2f17';

    public const string ANDROID_REDIRECT_URI = 'com.monicahq.android:/oauth/callback';

    public const string IOS_REDIRECT_URI = 'com.monicahq.ios:/oauth/callback';

    /** The return addresses of the mobile client, one per app. */
    public const array REDIRECT_URIS = [self::ANDROID_REDIRECT_URI, self::IOS_REDIRECT_URI];

    public const string SCOPE = 'api';

    public const int LIFETIME_IN_DAYS = 30;

    /**
     * How long after a renewal the refresh token it replaced may come back without ending the session:
     * two requests of the phone sent at once present the same token (RFC 9700, section 4.14.2).
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
            'last_used_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Get the user signed in on the phone.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the OAuth client of the app the phone signed in through.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Keep the sessions still running: not ended, and renewed within the life of a refresh token.
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
     * The sessions nobody can use again: ended, or unused past the life of a refresh token.
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
     * Resolve a session named in a URL, and only ever one of your own. A session somebody does
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
