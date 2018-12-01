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

$passports = [

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    |
    | Passport uses encryption keys while generating secure access tokens for
    | your application. By default, the keys are stored as local files but
    | can be set via environment variables when that is more convenient.
    |
    */

    'private_key' => env('PASSPORT_PRIVATE_KEY'),

    'public_key' => env('PASSPORT_PUBLIC_KEY'),

];

// Use fortrabbit secrets
if (isset($_SERVER['APP_SECRETS'])) {
    $secrets = json_decode(file_get_contents($_SERVER['APP_SECRETS']), true);

    if (isset($secrets['CUSTOM']['PASSPORT_PRIVATE_KEY'])) {
        $passports['private_key'] = str_replace('\\\\n', '\\n', $secrets['CUSTOM']['PASSPORT_PRIVATE_KEY']);
    }
    if (isset($secrets['CUSTOM']['PASSPORT_PUBLIC_KEY'])) {
        $passports['public_key'] = str_replace('\\\\n', '\\n', $secrets['CUSTOM']['PASSPORT_PUBLIC_KEY']);
    }
}

return $passports;
