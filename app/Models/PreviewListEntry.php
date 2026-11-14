<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PreviewListEntryFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Override;

/**
 * Class PreviewListEntry
 *
 * An email address waiting for an account while registration is closed. It belongs to the instance,
 * never to an account. An administrator admits it, which emails a link that opens sign up for that
 * address alone. Only the hash of the link's token is kept, so the link cannot be rebuilt from the
 * database.
 *
 * The state is read from the dates: registered once `registered_at` is set, admitted once
 * `admitted_at` is set, waiting otherwise.
 *
 * @property int $id
 * @property string $email
 * @property string $locale
 * @property string|null $token
 * @property CarbonImmutable|null $token_expires_at
 * @property CarbonImmutable|null $admitted_at
 * @property CarbonImmutable|null $registered_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'email',
    'locale',
    'token',
    'token_expires_at',
    'admitted_at',
    'registered_at',
])]
class PreviewListEntry extends Model implements HasLocalePreference
{
    /** @use HasFactory<PreviewListEntryFactory> */
    use HasFactory;

    use Notifiable;

    public const int ADMISSION_DAYS = 14;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'admitted_at' => 'datetime',
            'registered_at' => 'datetime',
        ];
    }

    /**
     * Get the entry whose admission link carries this token, while that link still opens sign up:
     * the token is the latest one sent, the entry is not registered and the link has not expired.
     * This is the one place that decides it, for the link, the middleware and the sign up forms.
     */
    public static function findByAdmissionToken(string $token): ?self
    {
        if ($token === '') {
            return null;
        }

        return self::query()
            ->where('token', hash('sha256', $token))
            ->whereNull('registered_at')
            ->where('token_expires_at', '>', now())
            ->first();
    }

    /**
     * Get the language the admission email is written in.
     */
    #[Override]
    public function preferredLocale(): string
    {
        return $this->locale;
    }
}
