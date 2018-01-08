<?php

namespace App\Helpers;

use Vinkla\Hashids\Facades\Hashids;

class ID_hasher
{

    public function encode_id($id)
    {
        return Hashids::encode($id);
    }

    public function decode_id($hash)
    {
        return Hashids::decode($hash);
    }

}
