<?php

namespace App\Traits;

use App\Helpers\ID_hasher;

trait Hasher
{
    public function getRouteKey()
    {
        $ID_hasher = new ID_hasher();

        return $ID_hasher->encode_id(parent::getRouteKey());
    }

    public function resolveRouteBinding($value)
    {
        $ID_hasher = new ID_hasher();

        $value = $ID_hasher->decode_id($value);

        return $this->where($this->getRouteKeyName(), $value)->first();
    }
}
