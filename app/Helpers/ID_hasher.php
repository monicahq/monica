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
        if (str_contains($hash, 'h')) {
            $result = Hashids::decode(str_after($hash, 'h'));

            return $result[0]; // result is always an array due to quirk in Hashids libary
        } else {
            return $hash;
        }
    }
}
