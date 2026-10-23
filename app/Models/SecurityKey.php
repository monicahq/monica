<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SecurityKeyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Support\Aaguids;
use Override;

/**
 * Class SecurityKey
 *
 * One WebAuthn credential registered as a second factor for the single person
 * on an account. It answers the two factor challenge after the password, the
 * emailed link or an outside provider, and never signs anybody in on its own.
 *
 * It lives in a table of its own rather than beside the passkeys, because the
 * passkey sign-in finds a credential by its id alone: a key kept with the
 * passkeys, and checking a PIN, would sign somebody in without anything else.
 *
 * The columns are the passkeys' columns, so the package's serialiser reads
 * both, and the scoping is the passkeys' scoping: a key named in a URL is
 * only ever one of your own.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $credential_id
 * @property array<string, mixed> $credential
 * @property CarbonImmutable|null $last_used_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 * @property-read string|null $authenticator
 */
#[Fillable([
    'name',
    'credential_id',
    'credential',
    'last_used_at',
])]
class SecurityKey extends Model
{
    /** @use HasFactory<SecurityKeyFactory> */
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
            'credential' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Get the user the key answers the challenge for.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the make and model of the authenticator, read from the AAGUID the
     * credential carries, or null when the authenticator does not say. Keys
     * without a PIN often do not.
     *
     * @return Attribute<string|null, never>
     */
    protected function authenticator(): Attribute
    {
        return Attribute::get(function (): ?string {
            $aaguid = $this->credential['aaguid'] ?? null;

            if (! is_string($aaguid) || $aaguid === Aaguids::unknown()) {
                return null;
            }

            return Aaguids::labelFor($aaguid);
        });
    }

    /**
     * Whether the key has ever answered a challenge.
     */
    public function hasBeenUsed(): bool
    {
        return $this->last_used_at !== null;
    }

    /**
     * Resolve a key named in a URL, and only ever one of your own. A key
     * somebody does not own fails as though it did not exist.
     */
    #[Override]
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('user_id', Auth::id())
            ->first();
    }
}
