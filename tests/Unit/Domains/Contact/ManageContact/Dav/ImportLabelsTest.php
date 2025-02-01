<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Dav;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Domains\Contact\ManageContact\Dav\ImportLabels;
use App\Models\Contact;
use App\Models\Label;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;

class ImportLabelsTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    #[Group('dav')]
    #[Test]
    public function it_imports_one_label()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportLabels;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $vcard = new VCard([
            'CATEGORIES' => ['tag'],
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->labels);
        $label = $contact->labels->first();
        $this->assertEquals('tag', $label->name);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_multiple_labels()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportLabels;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $vcard = new VCard([
            'CATEGORIES' => ['tag1', 'tag2'],
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(2, $contact->labels);
        $labels = $contact->labels->pluck('name')->toArray();
        $this->assertContains('tag1', $labels);
        $this->assertContains('tag2', $labels);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_labels_and_remove_old_labels()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportLabels;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $label = Label::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $contact->labels()->syncWithoutDetaching($label);

        $vcard = new VCard([
            'CATEGORIES' => ['tag1', 'tag2'],
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(2, $contact->labels);
        $labels = $contact->labels->pluck('name')->toArray();
        $this->assertNotContains($label->name, $labels);
        $this->assertContains('tag1', $labels);
        $this->assertContains('tag2', $labels);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_labels_no_change()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportLabels;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $label = Label::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $contact->labels()->syncWithoutDetaching($label);

        $vcard = new VCard([
            'CATEGORIES' => [$label->name],
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->labels);
        $labels = $contact->labels->pluck('name')->toArray();
        $this->assertContains($label->name, $labels);
    }
}
