<?php

namespace Tests\Unit\Services\VCard;

use Tests\TestCase;
use Tests\Api\DAV\CardEtag;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use Sabre\VObject\Component\VCard;
use App\Services\VCard\ExportVCard;
use App\Models\Contact\ContactField;
use Sabre\VObject\PHPUnitAssertions;
use App\Models\Contact\ContactFieldType;
use App\Models\Contact\ContactFieldLabel;
use App\Services\Contact\Tag\AssociateTag;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExportVCardTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions,
        CardEtag;

    /** @var int */
    const defaultPropsCount = 3;

    /** @test */
    public function vcard_add_names()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $vCard = new VCard();

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportNames', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 2,
            $vCard->children()
        );
        $this->assertStringContainsString('FN:John Doe', $vCard->serialize());
        $this->assertStringContainsString('N:Doe;John;;;', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_nickname()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'nickname' => 'the nickname',
        ]);
        $vCard = new VCard();

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportNames', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 3,
            $vCard->children()
        );
        $this->assertStringContainsString('FN:John Doe', $vCard->serialize());
        $this->assertStringContainsString('N:Doe;John;;;', $vCard->serialize());
        $this->assertStringContainsString('NICKNAME:the nickname', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_gender()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $vCard = new VCard();

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportGender', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('GENDER:M', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_gender_female()
    {
        $account = factory(Account::class)->create();
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
            'type' => 'F',
            'name' => 'Female',
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'gender_id' => $gender->id,
        ]);
        $vCard = new VCard();

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportGender', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('GENDER:F', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_gender_unknown()
    {
        $account = factory(Account::class)->create();
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
            'type' => 'U',
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'gender_id' => $gender->id,
        ]);
        $vCard = new VCard();

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportGender', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('GENDER:U', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_gender_type_null()
    {
        $account = factory(Account::class)->create();
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
            'type' => null,
            'name' => 'Something',
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'gender_id' => $gender->id,
        ]);
        $vCard = new VCard();

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportGender', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('GENDER:O', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_gender_type_null_male()
    {
        $account = factory(Account::class)->create();
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
            'type' => null,
            'name' => 'Male',
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'gender_id' => $gender->id,
        ]);
        $vCard = new VCard();

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportGender', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('GENDER:O', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_gender_type_null_female()
    {
        $account = factory(Account::class)->create();
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
            'type' => null,
            'name' => 'Woman',
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'gender_id' => $gender->id,
        ]);
        $vCard = new VCard();

        $exportVCard = new ExportVCard();
        $this->invokePrivateMethod($exportVCard, 'exportGender', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('GENDER:F', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_photo()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $contact->avatar_source = 'gravatar';
        $contact->avatar_gravatar_url = 'gravatar';

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportPhoto', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('PHOTO;VALUE=URI:gravatar', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_work_org()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'company' => 'the company',
        ]);
        $vCard = new VCard();

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportWorkInformation', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('ORG:the company', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_work_title()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'job' => 'job position',
        ]);
        $vCard = new VCard();

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportWorkInformation', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('TITLE:job position', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_work_information()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'company' => 'the company',
            'job' => 'job position',
        ]);
        $vCard = new VCard();

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportWorkInformation', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 2,
            $vCard->children()
        );
        $this->assertStringContainsString('ORG:the company', $vCard->serialize());
        $this->assertStringContainsString('TITLE:job position', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_birthday()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $contact->setSpecialDate('birthdate', 2000, 10, 5);
        $vCard = new VCard();

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportBirthday', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('BDAY:20001005', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_birthday_with_unknown_year()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $contact->setSpecialDate('birthdate', 0, 10, 5);
        $vCard = new VCard();

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportBirthday', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('BDAY:--1005', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_contact_fields_empty()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportContactFields', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount,
            $vCard->children()
        );
    }

    /** @test */
    public function vcard_add_contact_fields()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $contactFieldType = factory(ContactFieldType::class)->create(['account_id' => $account->id]);
        factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportContactFields', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('EMAIL:john@doe.com', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_contact_fields_email_labels()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $contactFieldType = factory(ContactFieldType::class)->create(['account_id' => $account->id]);
        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);
        $contactFieldLabel = factory(ContactFieldLabel::class)->create([
            'account_id' => $account->id,
        ]);
        $contactField->labels()->attach($contactFieldLabel->id, ['account_id' => $account->id]);

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportContactFields', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('EMAIL;TYPE=WORK:john@doe.com', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_contact_fields_tel_labels()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
            'type' => 'phone',
        ]);
        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'data' => '0123456789',
            'contact_field_type_id' => $contactFieldType->id,
        ]);
        $contactFieldLabel = factory(ContactFieldLabel::class)->create([
            'account_id' => $account->id,
        ]);
        $contactField->labels()->attach($contactFieldLabel->id, ['account_id' => $account->id]);

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportContactFields', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('TEL;TYPE=WORK:0123456789', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_contact_fields_personal_labels()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $account->id,
        ]);
        $contactFieldLabel = factory(ContactFieldLabel::class)->create([
            'account_id' => $account->id,
            'label_i18n' => null,
            'label' => 'Something',
        ]);
        $contactField->labels()->attach($contactFieldLabel->id, ['account_id' => $account->id]);

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportContactFields', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('EMAIL;TYPE=Something:john@doe.com', $vCard->serialize());
    }

    /**
     * @test
     * @dataProvider socialProfileProvider
     */
    public function vcard_add_social_profile($name, $type, $data, $result)
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
            'name' => $name,
            'type' => $type,
        ]);
        factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => $data,
        ]);

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportContactFields', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString($result, $vCard->serialize());
    }

    public function socialProfileProvider()
    {
        return [
            ['Facebook', 'Facebook', 'test', 'SOCIALPROFILE;TYPE=facebook:https://www.facebook.com/test'],
            ['Twitter', 'Twitter', 'test', 'SOCIALPROFILE;TYPE=twitter:https://twitter.com/test'],
            ['Whatsapp', 'Whatsapp', 'test', 'SOCIALPROFILE;TYPE=whatsapp:https://wa.me/test'],
            ['Telegram', 'Telegram', 'test', 'SOCIALPROFILE;TYPE=telegram:http://t.me/test'],
            ['LinkedIn', 'LinkedIn', 'test', 'SOCIALPROFILE;TYPE=linkedin:http://www.linkedin.com/in/test'],
        ];
    }

    /**
     * @test
     * @dataProvider contactUrlProvider
     */
    public function vcard_add_contact_url($name, $protocol, $data, $result)
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
            'name' => $name,
            'protocol' => $protocol,
            'type' => 'URL',
        ]);
        factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => $data,
        ]);

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportContactFields', [$contact, $vCard]);
        $this->assertStringContainsString($result, $vCard->serialize());
    }

    public function contactUrlProvider()
    {
        return [
            ['Discord', 'https://www.discord.app/user/', 'test123', 'URL;VALUE=URI:https://www.discord.app/user/test123'],
            ['Facebook Profile', 'https://www.facebook.com/', 'test123', 'URL;VALUE=URI:https://www.facebook.com/test123'],
        ];
    }

    /** @test */
    public function vcard_add_addresses_empty()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportAddress', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount,
            $vCard->children()
        );
    }

    /** @test */
    public function vcard_add_addresses()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        factory(Address::class)->create([
            'contact_id' => $contact->id,
            'name' => 'Home',
            'account_id' => $account->id,
        ]);
        factory(Address::class)->create([
            'contact_id' => $contact->id,
            'name' => 'Home',
            'account_id' => $account->id,
        ]);

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportAddress', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 2,
            $vCard->children()
        );
        $this->assertStringContainsString('ADR:;;12;beverly hills;;90210;US', $vCard->serialize());
        $this->assertStringContainsString('ADR:;;12;beverly hills;;90210;US', $vCard->serialize());
    }

    /** @test */
    public function vcard_add_addresses_with_labels()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $vCard = new VCard();

        $address = factory(Address::class)->create([
            'contact_id' => $contact->id,
            'name' => 'Home',
            'account_id' => $account->id,
        ]);

        $contactFieldLabel = factory(ContactFieldLabel::class)->create([
            'account_id' => $account->id,
        ]);
        $address->labels()->attach($contactFieldLabel->id, ['account_id' => $account->id]);

        $exportVCard = app(ExportVCard::class);
        $this->invokePrivateMethod($exportVCard, 'exportAddress', [$contact, $vCard]);

        $this->assertCount(
            self::defaultPropsCount + 1,
            $vCard->children()
        );
        $this->assertStringContainsString('ADR;TYPE=WORK:;;12;beverly hills;;90210;US', $vCard->serialize());
    }

    /** @test */
    public function vcard_prepares_an_almost_empty_vcard()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id])->refresh();

        $exportVCard = app(ExportVCard::class);
        $vCard = $this->invokePrivateMethod($exportVCard, 'export', [$contact]);

        $this->assertCount(
            self::defaultPropsCount + 6,
            $vCard->children()
        );

        $this->assertVObjectEqualsVObject($this->getCard($contact), $vCard);
    }

    /** @test */
    public function vcard_prepares_a_complete_vcard()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);

        factory(Address::class)->create([
            'contact_id' => $contact->id,
            'name' => 'Home',
            'account_id' => $account->id,
        ]);

        factory(Address::class)->create([
            'contact_id' => $contact->id,
            'name' => 'Home',
            'account_id' => $account->id,
        ]);

        $contactFieldType = factory(ContactFieldType::class)->create(['account_id' => $account->id]);
        factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);

        $exportVCard = app(ExportVCard::class);
        $contact = $contact->refresh();
        $vCard = $this->invokePrivateMethod($exportVCard, 'export', [$contact]);

        $this->assertCount(
            self::defaultPropsCount + 9,
            $vCard->children()
        );

        $this->assertVObjectEqualsVObject($this->getCard($contact), $vCard);
    }

    /** @test */
    public function vcard_with_tags()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        app(AssociateTag::class)->execute([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'name' => 'tag1',
        ]);
        app(AssociateTag::class)->execute([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'name' => 'tag2',
        ]);

        $exportVCard = app(ExportVCard::class);
        $contact = $contact->refresh();
        $vCard = $this->invokePrivateMethod($exportVCard, 'export', [$contact]);

        $this->assertCount(
            self::defaultPropsCount + 7,
            $vCard->children()
        );

        $this->assertVObjectEqualsVObject($this->getCard($contact), $vCard);
    }
}
