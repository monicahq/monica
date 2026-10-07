<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Dav;

use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Reader;
use Tests\Fixtures\Http\Dav\ExposedCardDavPlugin;
use Tests\TestCase;

class CardDavPluginTest extends TestCase
{
    private const string CARD = "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:minji\r\n"
        ."N;ALTID=1;PROP-ID=a;LANGUAGE=ko;SCRIPT=Hang:김;민지;;;\r\n"
        ."FN;ALTID=1;PROP-ID=a;LANGUAGE=ko;SCRIPT=Hang;PREF=1:김민지\r\n"
        ."N;ALTID=1;PROP-ID=b;SCRIPT=Latn;X-NAME-KIND=transliteration:Kim;Min-ji;;;\r\n"
        ."FN;ALTID=1;PROP-ID=b;SCRIPT=Latn;X-NAME-KIND=transliteration:Kim Min-ji\r\n"
        ."N;ALTID=1;PROP-ID=c;PHONETIC=x-rr:Gim;Minji;;;\r\n"
        ."END:VCARD\r\n";

    #[Test]
    public function it_hands_a_vcard_3_client_the_displayed_name_alone(): void
    {
        $card = Reader::read(new ExposedCardDavPlugin()->convert(self::CARD, 'vcard3'));

        $this->assertCount(1, $card->select('N'));
        $this->assertCount(1, $card->select('FN'));
        $this->assertSame('김민지', (string) $card->FN);
        $this->assertNull($card->N['ALTID']);
        $this->assertNull($card->N['PROP-ID']);
        $this->assertNull($card->FN['PROP-ID']);
    }

    #[Test]
    public function it_hands_a_vcard_4_client_every_way_the_name_is_written(): void
    {
        $card = Reader::read(new ExposedCardDavPlugin()->convert(self::CARD, 'vcard4'));

        $this->assertCount(3, $card->select('N'));
        $this->assertCount(2, $card->select('FN'));
    }
}
