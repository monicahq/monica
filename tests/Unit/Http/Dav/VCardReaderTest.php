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

        $this->assertEquals([
            'last_name' => 'Schrute',
            'first_name' => 'Dwight',
            'middle_name' => 'Kurt',
            'prefix' => 'Dr',
            'suffix' => 'III',
            'nickname' => 'Beet farmer',
        ], $reader->nameParts());
        $this->assertTrue($reader->hasAName());
    }

    #[Test]
    public function it_says_when_a_card_carries_no_name_part(): void
    {
        $reader = new VCardReader("BEGIN:VCARD\r\nVERSION:4.0\r\nFN:Mose Schrute\r\nEND:VCARD\r\n");

        $this->assertFalse($reader->hasAName());
        $this->assertEquals('Mose Schrute', $reader->fallbackFirstName());
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
