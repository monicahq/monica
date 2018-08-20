<?php

namespace App\Helpers;

use Vinkla\Hashids\Facades\Hashids;
use App\Exceptions\WrongIdException;

class IdHasher
{
    /**
     * Prefix for ids.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Create a new IdHasher.
     *
     * @param string|null $prefix
     */
    public function __construct($prefix = null)
    {
        $this->prefix = $prefix ?? config('hashids.default_prefix');
    }

    public function encodeId($id)
    {
        return $this->prefix.Hashids::encode($id);
    }

    public function decodeId($hash)
    {
        if (starts_with($hash, $this->prefix)) {
            $result = Hashids::decode(str_after($hash, $this->prefix));

            if (! is_null($result) && count($result) > 0) {
                return $result[0]; // result is always an array due to quirk in Hashids libary
            }
        }

        throw new WrongIdException();
    }
}
