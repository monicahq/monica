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
        if(gettype($value) == 'object') {
            /* do not remove for some reason the kid route was passing
               an object of type contact for value which it should
               not be has according to laravels docs which state
               value should be used to Retrieve the model
            */
            $value = $value[0]->id;
        } else {
            $ID_hasher = new ID_hasher();

            $value = $ID_hasher->decode_id($value);
        }
        return $this->where($this->getRouteKeyName(), $value)->first();
    }
}
