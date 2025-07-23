<?php

namespace Tests\Unit\Domains\Contact\ManageContactInformation\Dav;

use App\Domains\Contact\Dav\Services\ImportVCard;
use App\Domains\Contact\ManageContactInformation\Dav\ImportContactInformation;
use App\Models\Contact;
use App\Models\ContactInformation;
use App\Models\ContactInformationType;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\PHPUnitAssertions;
use Tests\TestCase;

class ImportContactInformationTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    #[Group('dav')]
    #[Test]
    public function it_imports_new_email()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        ContactInformationType::factory()->create([
            'account_id' => $vault->account_id,
        ]);

        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportContactInformation;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $vcard = new VCard([
            'EMAIL' => 'test@test.com',
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->contactInformations);
        $email = $contact->contactInformations->first();
        $this->assertEquals('test@test.com', $email->data);
        $this->assertEquals('email', $email->contactInformationType->type);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_new_email_type()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        ContactInformationType::factory()->create([
            'account_id' => $vault->account_id,
        ]);

        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportContactInformation;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $vcard = new VCard;
        $vcard->add('EMAIL', 'test@test.com', [
            'TYPE' => 'home',
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->contactInformations);
        $email = $contact->contactInformations->first();
        $this->assertEquals('test@test.com', $email->data);
        $this->assertEquals('email', $email->contactInformationType->type);
        $this->assertEquals('home', $email->kind);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_new_email_with_type_creation()
    {
        $user = $this->createAdministrator();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);

        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportContactInformation;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $vcard = new VCard;
        $vcard->add('EMAIL', 'test@test.com', [
            'TYPE' => 'home',
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->contactInformations);
        $email = $contact->contactInformations->first();
        $this->assertEquals('test@test.com', $email->data);
        $this->assertEquals('EMAIL', $email->contactInformationType->type);
        $this->assertEquals('home', $email->kind);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_new_tel()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        ContactInformationType::factory()->create([
            'account_id' => $vault->account_id,
            'type' => 'phone',
            'protocol' => 'tel:',
        ]);

        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportContactInformation;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $vcard = new VCard([
            'TEL' => '1234567890',
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->contactInformations);
        $tel = $contact->contactInformations->first();
        $this->assertEquals('1234567890', $tel->data);
        $this->assertEquals('phone', $tel->contactInformationType->type);
    }

    #[Group('dav')]
    #[Test]
    public function it_imports_tel_with_type()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        ContactInformationType::factory()->create([
            'account_id' => $vault->account_id,
            'type' => 'phone',
            'protocol' => 'tel:',
        ]);

        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportContactInformation;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $vcard = new VCard;
        $vcard->add('TEL', '1234567890', [
            'TYPE' => 'cell',
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->contactInformations);
        $tel = $contact->contactInformations->first();
        $this->assertEquals('1234567890', $tel->data);
        $this->assertEquals('phone', $tel->contactInformationType->type);
        $this->assertEquals('cell', $tel->kind);
    }

    #[Group('dav')]
    #[Test]
    #[DataProvider('social_profiles')]
    public function it_imports_social_profile(string $type, string $value)
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        ContactInformationType::factory()->create([
            'account_id' => $vault->account_id,
            'type' => 'X-SOCIAL-PROFILE',
            'name_translation_key' => $type,
        ]);

        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportContactInformation;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $vcard = new VCard;
        $vcard->add('X-SOCIAL-PROFILE', '', [
            'TYPE' => $type,
            'X-USER' => $value,
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->contactInformations);
        $socialProfile = $contact->contactInformations->first();
        $this->assertEquals($value, $socialProfile->data);
        $this->assertEquals('X-SOCIAL-PROFILE', $socialProfile->contactInformationType->type);
        $this->assertEquals($type, $socialProfile->contactInformationType->name_translation_key);
    }

    public static function social_profiles(): iterable
    {
        return [
            ['Facebook', 'test'],
            ['Whatsapp', 'test'],
            ['Instagram', 'test'],
        ];
    }

    #[Group('dav')]
    #[Test]
    #[DataProvider('impps')]
    public function it_imports_impp(string $type, string $value)
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        ContactInformationType::factory()->create([
            'account_id' => $vault->account_id,
            'type' => 'IMPP',
            'name_translation_key' => $type,
        ]);

        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportContactInformation;
        $importer->setContext($importVCard);

        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $vcard = new VCard;
        $vcard->add('IMPP', $value, [
            'X-SERVICE-TYPE' => $type,
        ]);

        $contact = $importer->import($vcard, $contact);

        $this->assertCount(1, $contact->contactInformations);
        $socialProfile = $contact->contactInformations->first();
        $this->assertEquals($value, $socialProfile->data);
        $this->assertEquals('IMPP', $socialProfile->contactInformationType->type);
        $this->assertEquals($type, $socialProfile->contactInformationType->name_translation_key);
    }

    #[Group('dav')]
    #[Test]
    public function it_updates_email_address()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $type = ContactInformationType::factory()->create([
            'account_id' => $vault->account_id,
        ]);
        $contactInformation = ContactInformation::factory()->create([
            'contact_id' => $contact->id,
            'type_id' => $type->id,
        ]);

        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportContactInformation;
        $importer->setContext($importVCard);

        $vcard = new VCard([
            'EMAIL' => 'test@test.com',
        ]);

        $contact = $importer->import($vcard, $contact);

        $contactInformation->refresh();

        $this->assertCount(1, $contact->contactInformations);
        $this->assertEquals('test@test.com', $contactInformation->data);
        $this->assertEquals('email', $contactInformation->contactInformationType->type);
    }

    #[Group('dav')]
    #[Test]
    public function it_destroys_email_address()
    {
        $this->expectException(ModelNotFoundException::class);

        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $type = ContactInformationType::factory()->create([
            'account_id' => $vault->account_id,
        ]);
        $contactInformation = ContactInformation::factory()->create([
            'contact_id' => $contact->id,
            'type_id' => $type->id,
        ]);
        ContactInformationType::factory()->create([
            'account_id' => $vault->account_id,
            'type' => 'phone',
            'protocol' => 'tel:',
        ]);

        $importVCard = new ImportVCard;
        $importVCard->author = $user;
        $importVCard->vault = $vault;
        $importer = new ImportContactInformation;
        $importer->setContext($importVCard);

        $vcard = new VCard;
        $vcard->add('TEL', '1234567890', [
            'TYPE' => 'cell',
        ]);

        $contact = $importer->import($vcard, $contact);

        $contactInformation->refresh();
    }

    public static function impps(): iterable
    {
        return [
            ['Telegram', 'test'],
            ['Hangouts', 'test'],
        ];
    }
}
