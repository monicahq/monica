<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\ManageContact\Dav\ExportContactInformation;
use App\Models\Contact;
use App\Models\ContactInformation;
use App\Models\ContactInformationType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class ExportContactInformationTest extends TestCase
{
    use CardEtag,
        DatabaseTransactions,
        PHPUnitAssertions;

    /** @var int */
    const defaultPropsCount = 3;

    #[Group('dav')]
    #[Test]
    public function it_adds_email_to_vcard()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $type = ContactInformationType::factory()->create([
            'account_id' => $vault->account_id,
            'name' => 'Email address',
        ]);
        $info = ContactInformation::factory()->create([
            'contact_id' => $contact->id,
            'type_id' => $type->id,
            'data' => 'fake@email.com',
        ]);

        $vCard = new VCard();
        (new ExportContactInformation)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );

        $this->assertStringContainsString("EMAIL:{$info->data}", $vCard->serialize());
    }

    #[Group('dav')]
    #[Test]
    public function it_adds_tel_to_vcard()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $type = ContactInformationType::factory()->create([
            'account_id' => $vault->account_id,
            'name' => 'Phone',
            'type' => 'phone',
            'protocol' => 'tel:',
        ]);
        $info = ContactInformation::factory()->create([
            'contact_id' => $contact->id,
            'type_id' => $type->id,
            'data' => '1234567890',
        ]);

        $vCard = new VCard();
        (new ExportContactInformation)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );

        $this->assertStringContainsString("TEL:{$info->data}", $vCard->serialize());
    }
}
