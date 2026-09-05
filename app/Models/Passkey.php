<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PasskeyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Passkey as BasePasskey;
use Override;

/**
 * Class Passkey
 *
 * One credential registered against the single person on an account.
 *
 * Everything about the credential itself comes from the parent: the id, the
 * serialised record, the date it was last used, and the accessor that names the
 * device from its AAGUID. What is added here is the relation typed to this
 * application's user, the question the security screen asks, and the scoping
 * that decides who may reach a row at all.
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
class Passkey extends BasePasskey
{
    /** @use HasFactory<PasskeyFactory> */
    use HasFactory;

    /**
     * Whether the passkey has ever signed anybody in. The screen asks this
     * rather than comparing against null, because a passkey nobody has used yet
     * is a state it states out loud instead of leaving the column blank.
     */
    public function hasBeenUsed(): bool
    {
        return $this->last_used_at !== null;
    }

    /**
     * Resolve a passkey named in a URL, and only ever one of your own.
     *
     * The package binds `{passkey}` through this method and then checks
     * ownership itself with a 403. A record somebody does not own has to fail as
     * though it did not exist, so the scoping happens here instead: the binding
     * finds nothing, the route answers 404, and the package's 403 is never
     * reached.
     */
    #[Override]
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('user_id', Auth::id())
            ->first();
    }
}
