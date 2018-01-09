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
        dd($value);
        return $this->where($this->getRouteKeyName(), $value->id)->first();
    }
}
