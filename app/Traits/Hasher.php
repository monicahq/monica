<?php

namespace App\Traits;

use App\Services\Instance\IdHasher;

trait Hasher
{
    public function getRouteKey()
    {
        return app(IdHasher::class)->encodeId(parent::getRouteKey());
    }

    public function resolveRouteBinding($value)
    {
        $id = $this->decodeId($value);

        return parent::resolveRouteBinding($id);
    }

    protected function decodeId($value)
    {
        return app(IdHasher::class)->decodeId($value);
    }

    public function hashID()
    {
        return $this->getRouteKey();
    }
}
