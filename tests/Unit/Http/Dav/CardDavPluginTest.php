<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Dav;

use PHPUnit\Framework\Attributes\Test;
use Sabre\DAV\Exception\UnsupportedMediaType;
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

    #[Test]
    public function it_accepts_a_vcard_2_1_card(): void
    {
        $card = Reader::read(new ExposedCardDavPlugin()->validate("BEGIN:VCARD\r\nVERSION:2.1\r\nN;CHARSET=UTF-8;ENCODING=QUOTED-PRINTABLE:Beesly;Pam=C3=A9;;;\r\nTEL;CELL:5705550200\r\nEND:VCARD\r\n"));

        $this->assertSame(['Beesly', 'Pamé', '', '', ''], $card->N->getParts());
        $this->assertSame('5705550200', (string) $card->TEL);
    }

    #[Test]
    public function it_repairs_a_property_name_holding_an_underscore(): void
    {
        $card = Reader::read(new ExposedCardDavPlugin()->validate("BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nFN:Dwight Schrute\r\nX_BEET_FARM:Schrute Farms\r\nEND:VCARD\r\n"));

        $this->assertSame('Schrute Farms', (string) $card->{'X-BEET-FARM'});
    }

    #[Test]
    public function it_accepts_two_full_names_that_are_not_alternatives_of_each_other(): void
    {
        $card = Reader::read(new ExposedCardDavPlugin()->validate("BEGIN:VCARD\r\nVERSION:4.0\r\nUID:jim\r\nFN;LANGUAGE=en:Jim Halpert\r\nFN;LANGUAGE=fr:Jacques Halpert\r\nEND:VCARD\r\n"));

        $this->assertCount(2, $card->select('FN'));
    }

    #[Test]
    public function it_repairs_a_card_missing_its_identifier_and_its_version(): void
    {
        $card = Reader::read(new ExposedCardDavPlugin()->validate("BEGIN:VCARD\r\nFN:Kevin Malone\r\nEND:VCARD\r\n"));

        $this->assertNotSame('', (string) $card->UID);
        $this->assertNotNull($card->VERSION);
    }

    #[Test]
    public function it_refuses_a_body_that_is_not_a_card(): void
    {
        $this->expectException(UnsupportedMediaType::class);

        new ExposedCardDavPlugin()->validate('this is not a card');
    }

    #[Test]
    public function it_refuses_a_calendar_event(): void
    {
        $this->expectException(UnsupportedMediaType::class);

        new ExposedCardDavPlugin()->validate("BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Dunder Mifflin//EN\r\nEND:VCALENDAR\r\n");
    }

    #[Test]
    public function it_still_refuses_a_vcard_2_1_card_when_the_client_asks_for_strict_handling(): void
    {
        $this->expectException(UnsupportedMediaType::class);

        new ExposedCardDavPlugin()->validate("BEGIN:VCARD\r\nVERSION:2.1\r\nN:Beesly;Pam;;;\r\nFN:Pam Beesly\r\nEND:VCARD\r\n", 'handling=strict');
    }

    #[Test]
    public function it_keeps_a_line_with_no_colon_as_it_was_sent(): void
    {
        $card = Reader::read(new ExposedCardDavPlugin()->validate("BEGIN:VCARD\r\nVERSION:4.0\r\nUID:angela\r\nFN:Angela Martin\r\nthis line has no colon\r\nEMAIL:angela@dundermifflin.test\r\nEND:VCARD\r\n"));

        $this->assertSame('this line has no colon', (string) $card->{'X-MONICA-UNREADABLE-LINE'});
        $this->assertSame('Angela Martin', (string) $card->FN);
        $this->assertSame('angela@dundermifflin.test', (string) $card->EMAIL);
    }

    #[Test]
    public function it_sets_aside_only_the_unreadable_line_when_blank_lines_come_before_it(): void
    {
        $card = Reader::read(new ExposedCardDavPlugin()->validate("\r\nBEGIN:VCARD\r\nVERSION:4.0\r\nUID:angela\r\nFN:Angela Martin\r\n\r\n\r\nTEL:+15705550100\r\nthis line has no colon\r\nEND:VCARD\r\n"));

        $this->assertCount(1, $card->select('X-MONICA-UNREADABLE-LINE'));
        $this->assertSame('this line has no colon', (string) $card->{'X-MONICA-UNREADABLE-LINE'});
        $this->assertSame('+15705550100', (string) $card->TEL);
    }

    #[Test]
    public function it_keeps_a_property_whose_name_holds_a_space_as_it_was_sent(): void
    {
        $card = Reader::read(new ExposedCardDavPlugin()->validate("BEGIN:VCARD\r\nVERSION:4.0\r\nUID:angela\r\nFN:Angela Martin\r\nX CATS:Sprinkles, Bandit\r\nEND:VCARD\r\n"));

        $this->assertSame('X CATS:Sprinkles, Bandit', (string) $card->{'X-MONICA-UNREADABLE-LINE'});
    }

    #[Test]
    public function it_keeps_an_unreadable_line_with_its_continuation(): void
    {
        $card = Reader::read(new ExposedCardDavPlugin()->validate("BEGIN:VCARD\r\nVERSION:4.0\r\nUID:angela\r\nFN:Angela Martin\r\nthis line has no colon\r\n and goes on\r\nEND:VCARD\r\n"));

        $this->assertCount(1, $card->select('X-MONICA-UNREADABLE-LINE'));
        $this->assertSame("this line has no colon\n and goes on", (string) $card->{'X-MONICA-UNREADABLE-LINE'});
    }

    #[Test]
    public function it_hands_back_an_unreadable_line_as_it_was_sent(): void
    {
        $card = "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:angela\r\nFN:Angela Martin\r\nX-MONICA-UNREADABLE-LINE:this line has no colon\\, really\r\nEND:VCARD\r\n";

        foreach (['vcard4', 'vcard3'] as $target) {
            $converted = new ExposedCardDavPlugin()->convert($card, $target);

            $this->assertStringContainsString("\r\nthis line has no colon, really\r\n", $converted);
            $this->assertStringNotContainsString('X-MONICA-UNREADABLE-LINE', $converted);
        }
    }

    #[Test]
    public function it_hands_back_an_unreadable_line_with_its_continuation(): void
    {
        $card = "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:angela\r\nFN:Angela Martin\r\nX-MONICA-UNREADABLE-LINE:this line has no colon\\n and goes on\r\nEND:VCARD\r\n";

        $this->assertStringContainsString("\r\nthis line has no colon\r\n and goes on\r\nEND:VCARD", new ExposedCardDavPlugin()->convert($card, 'vcard4'));
    }

    #[Test]
    public function it_keeps_an_unreadable_line_as_a_property_in_json(): void
    {
        $card = "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:angela\r\nFN:Angela Martin\r\nX-MONICA-UNREADABLE-LINE:this line has no colon\r\nEND:VCARD\r\n";

        $this->assertStringContainsString('x-monica-unreadable-line', new ExposedCardDavPlugin()->convert($card, 'jcard'));
    }
}
