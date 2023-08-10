<?php

namespace App\Domains\Contact\Dav;

use Attribute;
use ReflectionAttribute;
use ReflectionClass;

#[Attribute]
class VCardType
{
    public function __construct(
        public string $type,
    ) {
    }

    /**
     * Test is $type is supported.
     */
    public static function is(ReflectionClass $class, string $type): bool
    {
        $attributes = $class->getAttributes(static::class, ReflectionAttribute::IS_INSTANCEOF);

        return empty($attributes)
            ? false
            : $attributes[0]->newInstance()->type === $type;
    }
}
