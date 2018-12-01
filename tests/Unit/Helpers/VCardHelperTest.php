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


namespace Tests\Unit\Helpers;

use Tests\FeatureTestCase;
use App\Helpers\VCardHelper;
use Sabre\VObject\Component\VCard;

class VCardHelperTest extends FeatureTestCase
{
    public function test_it_get_country_by_sabre_vcard()
    {
        $vcard = new VCard([
            'TEL' => '202-555-0191',
            'ADR' => ['', '', '17 Shakespeare Ave.', 'Southampton', '', 'SO17 2HB', 'United Kingdom'],
        ]);

        $iso = VCardHelper::getCountryISOFromSabreVCard($vcard);

        $this->assertEquals(
            'GB',
            $iso
        );
    }
}
