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
        $id = $this->decodeId($value);

        return parent::resolveRouteBinding($id);
    }

    protected function decodeId($value)
    {
        return app('idhasher')->decodeId($value);
    }

    public function hashID()
    {
        return $this->getRouteKey();
    }
}
