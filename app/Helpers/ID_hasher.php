<?php

namespace App\Helpers;

use Vinkla\Hashids\Facades\Hashids;

class ID_hasher
{

    public function encode_id($id)
    {
        return 'h'.Hashids::encode($id);
    }

    public function decode_id($hash)
    {
        if(str_contains($hash,'h')) {
            return Hashids::decode(str_after($hash,'h'));
        } else {
            return $hash;
        }
    }

}
