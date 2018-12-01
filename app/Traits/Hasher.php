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
