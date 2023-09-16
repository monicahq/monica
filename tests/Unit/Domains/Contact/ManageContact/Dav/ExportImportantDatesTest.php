<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\ManageContact\Dav\ExportImportantDates;
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
        $date = ContactImportantDate::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $vCard = new VCard();
        (new ExportImportantDates)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );

        $this->assertStringContainsString('BDAY:19811029', $vCard->serialize());
    }
}
