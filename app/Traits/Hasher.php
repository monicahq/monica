<?php

namespace App\Traits;

use App\Helpers\IdHasher;

trait Hasher
{
    public function getRouteKey()
    {
        return app('idhasher')->encodeId(parent::getRouteKey());
    }

    public function resolveRouteBinding($value)
    {
        $value = app('idhasher')->decodeId($value);

        return $this->where($this->getRouteKeyName(), $value)->first();
    }

    public function hashID()
    {
        return app('idhasher')->encodeId($this->id);
    }

    public function rawHashID()
    {
        return app('idhasher')->encodeRawId($this->id);
    }

    public function findOrFailByRawHashID($hash)
    {
        $value = app('idhasher')->decodeRawId($hash);

        return $this->findOrFail($value);
    }
}
