<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SsoProviderEnum;
use Carbon\CarbonImmutable;
use Database\Factories\ProviderConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Class ProviderConnection
 *
 * One outside provider that signs a user in. A user connects each provider at
 * most once, and a provider account signs in at most one user.
 *
 * @property int $id
 * @property int $user_id
 * @property SsoProviderEnum $provider
 * @property string $sso_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $user
 */
#[Fillable([
    'provider',
    'sso_id',
])]
class ProviderConnection extends Model
{
    /** @use HasFactory<ProviderConnectionFactory> */
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
            'provider' => SsoProviderEnum::class,
        ];
    }

    /**
     * Get the user the provider signs in.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
