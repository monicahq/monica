<?php

namespace Tests\Unit\Domains\Contact\ManageContactImportantDates\Dav;

use App\Domains\Contact\ManageContactImportantDates\Dav\ExportImportantDates;
use App\Models\Contact;
use App\Models\ContactImportantDate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class ExportImportantDatesTest extends TestCase
{
    use CardEtag,
        DatabaseTransactions,
        PHPUnitAssertions;

    /** @var int */
    const defaultPropsCount = 3;

    #[Group('dav')]
    #[Test]
    public function it_adds_bday_to_vcard()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create([
            'vault_id' => $vault->id,
        ]);
        ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $vCard = new VCard;
        (new ExportImportantDates)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );

        $this->assertStringContainsString('BDAY:19811029', $vCard->serialize());
    }

    #[Group('dav')]
    #[Test]
    public function it_adds_bday_to_vcard2()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create([
            'vault_id' => $vault->id,
        ]);
        ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
            'year' => 1981,
            'month' => null,
            'day' => null,
        ]);

        $vCard = new VCard;
        (new ExportImportantDates)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );

        $this->assertStringContainsString('BDAY:1981', $vCard->serialize());
    }

    #[Group('dav')]
    #[Test]
    public function it_adds_bday_to_vcard3()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create([
            'vault_id' => $vault->id,
        ]);
        ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
            'year' => null,
            'month' => 10,
            'day' => 27,
        ]);

        $vCard = new VCard;
        (new ExportImportantDates)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );

        $this->assertStringContainsString('BDAY:--1027', $vCard->serialize());
    }
}
