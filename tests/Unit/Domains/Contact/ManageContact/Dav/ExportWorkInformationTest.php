<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\ManageContact\Dav\ExportWorkInformation;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class ExportWorkInformationTest extends TestCase
{
    use CardEtag,
        DatabaseTransactions,
        PHPUnitAssertions;

    /** @var int */
    const defaultPropsCount = 3;

    #[Group('dav')]
    #[Test]
    public function it_adds_job_to_vcard()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $company = Company::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $contact = Contact::factory()->random()->create([
            'vault_id' => $vault->id,
            'company_id' => $company->id,
            'job_position' => 'random',
        ]);

        $vCard = new VCard;
        (new ExportWorkInformation)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 2,
            $vCard->children()
        );

        $this->assertStringContainsString("ORG:{$company->name}", $vCard->serialize());
        $this->assertStringContainsString("TITLE:{$contact->job_position}", $vCard->serialize());
    }
}
