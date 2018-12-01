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

return [

    'cache' => [
        'enabled' => true,

        'service' => PragmaRX\Countries\Package\Services\Cache\Service::class,

        'duration' => 180,

        'directory' => sys_get_temp_dir().'/__PRAGMARX_COUNTRIES__/cache',
    ],

    'hydrate' => [
        'before' => false,

        'after' => false,

        'elements' => [
            'borders' => false,
            'cities' => false,
            'currencies' => false,
            'flag' => false,
            'geometry' => false,
            'states' => false,
            'taxes' => false,
            'timezones' => true,
            'timezones_times' => false,
            'topology' => false,
        ],
    ],

    'maps' => [
        'lca3' => 'cca3',
        'currencies' => 'currency',
    ],

    'validation' => [
        'enabled' => false,
        'rules' => [
            'country'           => 'name.common',
            'name'              => 'name.common',
            'nameCommon'        => 'name.common',
            'cca2',
            'cca2',
            'cca3',
            'ccn3',
            'cioc',
            'currencies'        => 'ISO4217',
            'language_short'    => 'ISO639_3',
        ],
    ],

];
