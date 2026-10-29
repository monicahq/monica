<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * Keep raw collation key bytes intact. PostgreSQL stores them as bytea, which it reads as a stream and
 * only accepts as a stream, so the bytes are converted at the boundary and the model always sees a string.
 *
 * @implements CastsAttributes<string, string>
 */
class CollationKey implements CastsAttributes
{
    #[Override]
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (! is_resource($value)) {
            return $value;
        }

        rewind($value);

        return stream_get_contents($value);
    }

    /** @return string|resource|null */
    #[Override]
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return self::bindable($value, $model->getConnection());
    }

    /** @return string|resource */
    public static function bindable(string $bytes, Connection $connection): mixed
    {
        if ($connection->getDriverName() !== 'pgsql') {
            return $bytes;
        }

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $bytes);
        rewind($stream);

        return $stream;
    }
}
