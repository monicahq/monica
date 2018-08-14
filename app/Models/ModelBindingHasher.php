<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class ModelBindingHasher extends Model
{
    public function getRouteKey()
    {
        return app('idhasher')->encodeId(parent::getRouteKey());
    }

    public function resolveRouteBinding($value)
    {
        $id = app('idhasher')->decodeId($value);

        return $this->where('account_id', auth()->user()->account_id)
            ->findOrFail($id);
    }

    public function hashID()
    {
        return $this->getRouteKey();
    }
}
