<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\InstanceSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Override;

/**
 * Class InstanceSetting
 *
 * One decision an operator made about this instance, overriding what the
 * deployment supplied. A row exists only for a setting somebody changed, which
 * is what makes reverting a delete rather than a write, and what lets the screen
 * tell a deliberate choice from a default without a second column.
 *
 * Every value is encrypted, not only the secrets. A conditional cast would mean
 * the model had to know a setting's kind before it could read its own column,
 * and encrypting a few dozen short rows costs nothing measurable. Nothing
 * queries a setting by its value.
 *
 * The cache entry is forgotten here rather than in the actions that write, so no
 * caller can forget it and leave the instance running on a value nobody chose
 * any more.
 *
 * @property int $id
 * @property string $name
 * @property string|null $value
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'name',
    'value',
])]
class InstanceSetting extends Model
{
    /** @use HasFactory<InstanceSettingFactory> */
    use HasFactory;

    public const string CACHE_KEY = 'instance-settings';

    public const int CACHE_SECONDS = 60;

    #[Override]
    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget(self::CACHE_KEY);
        });

        static::deleted(function (): void {
            Cache::forget(self::CACHE_KEY);
        });
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return [
            'value' => 'encrypted',
        ];
    }
}
