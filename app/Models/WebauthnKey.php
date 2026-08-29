<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\WebauthnKeyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelWebauthn\Models\WebauthnKey as BaseWebauthnKey;

/**
 * Class WebauthnKey
 *
 * One passkey registered against the single person on an account. The product
 * says passkey everywhere a reader can see; the class and the table keep the
 * package's word, because the package owns the table and the migration that
 * makes it.
 *
 * Everything about the credential itself comes from the parent: the id, the
 * public key, the counter, the trust path. What is added here is the date the
 * screen shows and the relation the rest of the codebase expects to find on a
 * model.
 *
 * The parent declares `$fillable`, `$visible` and `$casts` as properties, so
 * `used_at` is merged into each rather than redeclared. Redeclaring `$visible`
 * would drop the fields the package needs to serialise, which is a failure that
 * shows up a long way from here.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $credentialId
 * @property string $type
 * @property array<int, string> $transports
 * @property string $attestationType
 * @property string $trustPath
 * @property string $aaguid
 * @property string $credentialPublicKey
 * @property int $counter
 * @property CarbonImmutable|null $used_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 */
class WebauthnKey extends BaseWebauthnKey
{
    /** @use HasFactory<WebauthnKeyFactory> */
    use HasFactory;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->mergeFillable(['used_at']);
        $this->setVisible(array_merge($this->getVisible(), ['used_at']));
        $this->mergeCasts(['used_at' => 'datetime']);
    }

    /**
     * Get the user the passkey belongs to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether the passkey has ever signed anybody in. The screen asks this
     * rather than comparing against null, because a passkey nobody has used yet
     * is a state it states out loud instead of leaving the column blank.
     */
    public function hasBeenUsed(): bool
    {
        return $this->used_at !== null;
    }
}
