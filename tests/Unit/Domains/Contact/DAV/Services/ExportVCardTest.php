<?php

namespace Tests\Unit\Domains\Contact\DAV\Services;

use App\Domains\Contact\Dav\Services\ExportVCard;
use App\Models\Contact;
use App\Models\Gender;
use App\Models\Group;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class ExportVCardTest extends TestCase
{
    use CardEtag,
        DatabaseTransactions,
        PHPUnitAssertions;

    /**
     * @group dav
     *
     * @test
     */
    public function it_exports_a_contact()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);
        $contact = Contact::factory()->random()->create(['vault_id' => $vault->id]);

        $vCard = (new ExportVCard($this->app))->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertInstanceOf(VCard::class, $vCard);
        $this->assertVObjectEqualsVObject($this->getCard($contact), $vCard->serialize());
    }

    /**
     * @group dav
     *
     * @test
     */
    public function it_exports_a_contact_with_vcard()
    {
        $source = 'BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject 4.3.0//EN
SOURCE:**ANY**
UID:**ANY**
FN:Dr. John Doe III
N:Doe;John;;;
GENDER:M
REV:**ANY**
END:VCARD';

        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $gender = Gender::factory()->create([
            'account_id' => $vault->account_id,
            'type' => 'M',
        ]);
        $contact = Contact::factory()->random()->create([
            'vault_id' => $vault->id,
            'vcard' => $source,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
        ]);

        $vCard = (new ExportVCard($this->app))->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertInstanceOf(VCard::class, $vCard);
        $this->assertVObjectEqualsVObject(
            $source,
            $vCard->serialize()
        );
    }

    /**
     * @group dav
     *
     * @test
     */
    public function it_exports_a_group_with_vcard()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user);

        $gender = Gender::factory()->create([
            'account_id' => $vault->account_id,
            'type' => 'M',
        ]);

        $vcard = 'BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject 4.3.0//EN
SOURCE:**ANY**
UID:**ANY**
FN:Dr. John Doe III
N:Doe;John;;;
GENDER:M
REV:**ANY**
END:VCARD';

        $contact = Contact::factory()->random()->create([
            'vault_id' => $vault->id,
            'vcard' => $vcard,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender_id' => $gender->id,
        ]);

        $source = "BEGIN:VCARD
VERSION:4.0
PRODID:-//Sabre//Sabre VObject 4.3.0//EN
SOURCE:**ANY**
MEMBER:{$contact->id}
UID:**ANY**
KIND:group
FN:Colleagues
N:Colleagues;;;;
REV:**ANY**
END:VCARD";

        $group = Group::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'Colleagues',
            'vcard' => $source,
        ]);
        $group->contacts()->syncWithoutDetaching([
            $contact->id => ['group_type_role_id' => null],
        ]);

        $vCard = (new ExportVCard($this->app))->execute([
            'account_id' => $user->account_id,
            'author_id' => $user->id,
            'vault_id' => $vault->id,
            'group_id' => $group->id,
        ]);

        $this->assertInstanceOf(VCard::class, $vCard);
        $this->assertVObjectEqualsVObject(
            $source,
            $vCard->serialize()
        );
    }
}
