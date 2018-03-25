<?php

namespace App\Traits;

use App\Helpers\IdHasher;

trait Hasher
{
    public function getRouteKey()
    {
        $ID_hasher = new IdHasher();

        return $ID_hasher->encodeId(parent::getRouteKey());
    }

    public function resolveRouteBinding($value)
    {
        $ID_hasher = new IdHasher();

        $value = $ID_hasher->encodeId($value);

        return $this->where($this->getRouteKeyName(), $value)->first();
    }

    public function hashID()
    {
        $ID_hasher = new IdHasher();

        return $ID_hasher->encodeId($this->id);
    }
}
