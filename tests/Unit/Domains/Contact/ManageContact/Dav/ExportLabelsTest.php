<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\ManageContact\Dav\ExportLabels;
use App\Models\Contact;
use App\Models\Label;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class ExportLabelsTest extends TestCase
{
    use CardEtag,
        DatabaseTransactions,
        PHPUnitAssertions;

    /** @var int */
    const defaultPropsCount = 3;

    #[Group('dav')]
    #[Test]
    public function it_adds_categories_to_vcard()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $label = Label::factory()->create(['vault_id' => $vault->id]);
        $label->contacts()->attach($contact->id);

        $vCard = new VCard;
        (new ExportLabels)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );

        $this->assertStringContainsString("CATEGORIES:{$label->name}", $vCard->serialize());
    }

    #[Group('dav')]
    #[Test]
    public function it_adds_multiple_categories_to_vcard()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);
        $labels = Label::factory(3)->create(['vault_id' => $vault->id]);
        foreach ($labels as $label) {
            $label->contacts()->attach($contact->id);
        }

        $vCard = new VCard;
        (new ExportLabels)->export($contact, $vCard);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );

        $this->assertStringContainsString("CATEGORIES:{$labels->pluck('name')->implode(',')}", $vCard->serialize());
    }
}
