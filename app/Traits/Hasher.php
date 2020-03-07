<?php

namespace App\Traits;

use App\Services\Instance\IdHasher;

trait Hasher
{
    public function getRouteKey()
    {
        return app(IdHasher::class)->encodeId(parent::getRouteKey());
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $id = $this->decodeId($value);

        return parent::resolveRouteBinding($id, $field);
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
