<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



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
