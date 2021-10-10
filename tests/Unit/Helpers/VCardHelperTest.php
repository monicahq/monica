<?php

namespace Tests\Unit\Helpers;

use Tests\FeatureTestCase;
use App\Helpers\VCardHelper;
use Sabre\VObject\Component\VCard;

class VCardHelperTest extends FeatureTestCase
{
    /** @test */
    public function it_get_country_by_sabre_vcard()
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
