<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\ManageContact\Dav\ExportAddress;
use App\Models\Address;
use App\Models\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class ExportAddressTest extends TestCase
{
    use CardEtag,
        DatabaseTransactions,
        PHPUnitAssertions;

    /** @var int */
    const defaultPropsCount = 3;

    #[Group('dav')]
    #[Test]
    public function it_adds_address_to_vcard()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $address = Address::factory()->create(['vault_id' => $vault->id]);
        $address->contacts()->attach($contact->id);

        $vCard = new VCard;
        (new ExportAddress)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );

        $text = "ADR;TYPE={$address->addressType->type}:;{$address->line_1};{$address->line_2};{$address->city};{$address->province};{$address->postal_code};{$address->country}";
        $this->assertStringContainsString(Str::substr($text, 0, 75), $vCard->serialize());
        $this->assertStringContainsString(Str::substr($text, 75), $vCard->serialize());
    }
}
