<?php

namespace Tests\Unit\Domains\Contact\ManageGroups\Dav;

use App\Domains\Contact\ManageContact\Dav\ExportNames;
use App\Models\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Sabre\VObject\Component\VCard;
use Tests\TestCase;

class ExportNamesTest extends TestCase
{
    use DatabaseTransactions;

    /** @var int */
    const defaultPropsCount = 3;

    /**
     * @group dav
     *
     * @test
     */
    public function it_adds_names_in_vcard()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create([
            'vault_id' => $vault->id,
        ]);

        $vCard = new VCard;
        (new ExportNames)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 2,
            $vCard->children()
        );
        $this->assertStringContainsString("FN:{$contact->name}", $vCard->serialize());
        $this->assertStringContainsString("N:{$contact->last_name};{$contact->first_name};;;", $vCard->serialize());
    }

    /**
     * @group dav
     *
     * @test
     */
    public function it_adds_nickname_in_vcard()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->nickname()->create([
            'vault_id' => $vault->id,
        ]);

        $vCard = new VCard;
        (new ExportNames)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 3,
            $vCard->children()
        );
        $this->assertStringContainsString("FN:{$contact->name}", $vCard->serialize());
        $this->assertStringContainsString("N:{$contact->last_name};{$contact->first_name};;;", $vCard->serialize());
        $this->assertStringContainsString("NICKNAME:{$contact->nickname}", $vCard->serialize());
    }
}
