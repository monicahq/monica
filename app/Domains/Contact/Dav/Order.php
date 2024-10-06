<?php

namespace App\Domains\Contact\Dav;

use Attribute;
use ReflectionAttribute;
use ReflectionClass;

#[Attribute]
class Order
{
    public function __construct(
        public int $order,
    ) {}

    /**
     * Get order value from a reflection class.
     */
    public static function get(ReflectionClass $class): int
    {
        $attributes = $class->getAttributes(static::class, ReflectionAttribute::IS_INSTANCEOF);

        return empty($attributes)
            ? 0
            : $attributes[0]->newInstance()->order;
    }
}
