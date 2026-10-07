<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Dav;

use App\Http\Dav\VCardReader;
use PHPUnit\Framework\Attributes\Test;
use Sabre\DAV\Exception\BadRequest;
use Tests\TestCase;

class VCardReaderTest extends TestCase
{
    #[Test]
    public function it_reads_every_part_of_a_name(): void
    {
        $reader = new VCardReader("BEGIN:VCARD\r\nVERSION:4.0\r\nN:Schrute;Dwight;Kurt;Dr;III\r\nFN:Dwight Schrute\r\nNICKNAME:Beet farmer\r\nEND:VCARD\r\n");

        $row = $reader->nameRepresentations()[0];

        $this->assertSame('Schrute', $row['family_name']);
        $this->assertSame('Dwight', $row['given_name']);
        $this->assertSame('Kurt', $row['additional_name']);
        $this->assertSame('Dr', $row['prefix']);
        $this->assertSame('III', $row['suffix']);
        $this->assertSame('Dwight Schrute', $row['formatted_name']);
        $this->assertSame('Beet farmer', $reader->nickname());
    }

    #[Test]
    public function it_keeps_no_formatted_name_that_only_repeats_the_parts(): void
    {
        $reader = new VCardReader("BEGIN:VCARD\r\nVERSION:4.0\r\nN:Schrute;Dwight;;;\r\nFN:Dwight Schrute\r\nEND:VCARD\r\n");

        $this->assertNull($reader->nameRepresentations()[0]['formatted_name']);
    }

    #[Test]
    public function it_reads_a_full_name_written_without_parts(): void
    {
        $reader = new VCardReader("BEGIN:VCARD\r\nVERSION:4.0\r\nFN:Mose Schrute\r\nEND:VCARD\r\n");

        $rows = $reader->nameRepresentations();

        $this->assertCount(1, $rows);
        $this->assertSame('Mose Schrute', $rows[0]['formatted_name']);
        $this->assertNull($rows[0]['given_name']);
    }

    #[Test]
    public function it_reads_every_way_a_card_writes_the_name(): void
    {
        $reader = new VCardReader((string) file_get_contents(base_path('tests/Fixtures/Dav/multilingual-names.vcf')));

        $rows = $reader->nameRepresentations();

        $this->assertCount(4, $rows);
        $this->assertSame(['written', 'transliteration', 'written', 'phonetic'], array_column($rows, 'kind'));
        $this->assertSame(['Hang', 'Latn', 'Hani', 'Latn'], array_column($rows, 'script'));
        $this->assertSame(['김민지', null, '金敏智', null], array_column($rows, 'formatted_name'));
        $this->assertSame('x-rr', $rows[3]['phonetic_system']);
        $this->assertSame([true, false, false, false], array_column($rows, 'is_preferred'));
        $this->assertSame('1', $rows[0]['vcard_alt_id']);
    }

    #[Test]
    public function it_pairs_a_name_with_its_full_name_by_property_identifier_before_anything_else(): void
    {
        $reader = new VCardReader("BEGIN:VCARD\r\nVERSION:4.0\r\nFN;PROP-ID=b:Kim Min-ji\r\nFN;PROP-ID=a:김민지\r\nN;PROP-ID=a:김;민지;;;\r\nN;PROP-ID=b:Kim;Min-ji;;;\r\nEND:VCARD\r\n");

        $rows = $reader->nameRepresentations();

        $this->assertSame(['a', 'b'], array_column($rows, 'vcard_property_id'));
        $this->assertSame(['김민지', null], array_column($rows, 'formatted_name'));
    }

    #[Test]
    public function it_never_hands_a_name_the_full_name_of_another_property_identifier(): void
    {
        $reader = new VCardReader("BEGIN:VCARD\r\nVERSION:4.0\r\nN;PROP-ID=a:Scott;Michael;;;\r\nN;PROP-ID=b:Scarn;Michael;;;\r\nFN;PROP-ID=b;PREF=1:Agent Michael Scarn\r\nEND:VCARD\r\n");

        $rows = $reader->nameRepresentations();

        $this->assertSame(['a', 'b'], array_column($rows, 'vcard_property_id'));
        $this->assertSame([null, 'Agent Michael Scarn'], array_column($rows, 'formatted_name'));
        $this->assertSame([false, true], array_column($rows, 'is_preferred'));
    }

    #[Test]
    public function it_pairs_by_language_whatever_its_case_and_never_with_a_full_name_in_another_language(): void
    {
        $reader = new VCardReader("BEGIN:VCARD\r\nVERSION:4.0\r\nN;LANGUAGE=EN:Scott;Michael;;;\r\nFN;LANGUAGE=ja:マイケル・スコット\r\nFN;LANGUAGE=en:World's Best Boss\r\nEND:VCARD\r\n");

        $rows = $reader->nameRepresentations();

        $this->assertCount(2, $rows);
        $this->assertSame('World\'s Best Boss', $rows[0]['formatted_name']);
        $this->assertSame('Scott', $rows[0]['family_name']);
        $this->assertSame('マイケル・スコット', $rows[1]['formatted_name']);
        $this->assertNull($rows[1]['family_name']);
    }

    #[Test]
    public function it_gives_a_pronunciation_its_parts_and_never_a_full_name(): void
    {
        $reader = new VCardReader("BEGIN:VCARD\r\nVERSION:4.0\r\nN;PHONETIC=ipa:ʃruːt;ˈdwaɪt;;;\r\nN;PHONETIC=ipa:;;;;\r\nFN:Dwight Schrute\r\nEND:VCARD\r\n");

        $rows = $reader->nameRepresentations();

        $this->assertSame(['phonetic', 'written'], array_column($rows, 'kind'));
        $this->assertNull($rows[0]['formatted_name']);
        $this->assertSame('ʃruːt', $rows[0]['family_name']);
        $this->assertSame('Dwight Schrute', $rows[1]['formatted_name']);
    }

    #[Test]
    public function it_reads_a_script_code_whatever_its_case(): void
    {
        $reader = new VCardReader("BEGIN:VCARD\r\nVERSION:4.0\r\nN;SCRIPT=latn:Scott;Michael;;;\r\nFN;SCRIPT=LATN:Michael Scott\r\nEND:VCARD\r\n");

        $rows = $reader->nameRepresentations();

        $this->assertCount(1, $rows);
        $this->assertSame('Latn', $rows[0]['script']);
    }

    #[Test]
    public function it_cuts_every_value_to_the_room_the_record_has_for_it(): void
    {
        $long = str_repeat('a', 300);
        $tag = 'en-'.implode('-', array_fill(0, 10, 'scranton'));
        $reader = new VCardReader("BEGIN:VCARD\r\nVERSION:4.0\r\nN;ALTID={$long};PROP-ID={$long}x;LANGUAGE={$tag}:{$long};{$long};;{$long};{$long}\r\nFN:Michael Scott\r\nEND:VCARD\r\n");

        $row = $reader->nameRepresentations()[0];

        $this->assertSame(64, mb_strlen((string) $row['vcard_alt_id']));
        $this->assertSame(255, mb_strlen((string) $row['vcard_property_id']));
        $this->assertNull($row['language']);
        $this->assertSame(255, mb_strlen((string) $row['family_name']));
        $this->assertSame(255, mb_strlen((string) $row['given_name']));
        $this->assertSame(50, mb_strlen((string) $row['prefix']));
        $this->assertSame(50, mb_strlen((string) $row['suffix']));
    }

    #[Test]
    public function it_leaves_out_a_language_or_a_script_that_is_not_a_code(): void
    {
        $reader = new VCardReader("BEGIN:VCARD\r\nVERSION:4.0\r\nN;LANGUAGE=en_US;SCRIPT=latin:Scott;Michael;;;\r\nFN:Michael Scott\r\nEND:VCARD\r\n");

        $row = $reader->nameRepresentations()[0];

        $this->assertNull($row['language']);
        $this->assertNull($row['script']);
    }

    #[Test]
    public function it_hands_over_the_properties_of_one_kind_in_the_order_the_card_lists_them(): void
    {
        $reader = new VCardReader("BEGIN:VCARD\r\nVERSION:4.0\r\nFN:Dwight\r\nTEL:+15705551212\r\nTEL;TYPE=work:+15705550100\r\nEND:VCARD\r\n");

        $phones = $reader->propertiesOf('TEL');

        $this->assertCount(2, $phones);
        $this->assertEquals('+15705551212', $phones[0]->getValue());
        $this->assertEquals('+15705550100', $phones[1]->getValue());
    }

    #[Test]
    public function it_reads_a_photo_a_device_wrote_into_the_card(): void
    {
        $bytes = 'not really an image';
        $reader = new VCardReader("BEGIN:VCARD\r\nVERSION:4.0\r\nFN:Dwight\r\nPHOTO:data:image/webp;base64,".base64_encode($bytes)."\r\nEND:VCARD\r\n");

        $this->assertEquals($bytes, $reader->photo());
    }

    #[Test]
    public function it_reads_no_photo_from_a_card_that_only_links_to_one(): void
    {
        $reader = new VCardReader("BEGIN:VCARD\r\nVERSION:4.0\r\nFN:Dwight\r\nPHOTO;VALUE=uri:https://dundermifflin.test/dwight.jpg\r\nEND:VCARD\r\n");

        $this->assertNull($reader->photo());
    }

    #[Test]
    public function it_reads_a_card_of_the_older_version_as_the_newer_one(): void
    {
        $reader = new VCardReader("BEGIN:VCARD\r\nVERSION:3.0\r\nN:Schrute;Dwight;;;\r\nFN:Dwight Schrute\r\nBDAY;X-APPLE-OMIT-YEAR=1604:1604-01-20\r\nEND:VCARD\r\n");

        $this->assertEquals('4.0', (string) $reader->document()->VERSION);
        $this->assertEquals('--01-20', (string) $reader->document()->BDAY);
    }

    #[Test]
    public function it_refuses_something_that_is_not_a_card(): void
    {
        $this->expectException(BadRequest::class);

        new VCardReader('beets');
    }
}
