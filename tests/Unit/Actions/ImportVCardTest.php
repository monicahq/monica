<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateDefaultValueLists;
use App\Actions\ImportVCard;
use App\Actions\UpdateContactAvatar;
use App\Enums\AddressLabelEnum;
use App\Enums\ContactNameKindEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\NameOrderEnum;
use App\Enums\ValueListEnum;
use App\Http\Dav\VCardMerger;
use App\Jobs\CreateDefaultTemplate;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Sabre\DAV\Exception\BadRequest;
use Sabre\VObject\Reader;
use Tests\Fixtures\Dav\Structure;
use Tests\TestCase;

class ImportVCardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vault $vault;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id]);
    }

    #[Test]
    public function it_creates_a_contact_on_the_default_structure_of_the_vault(): void
    {
        $version = Structure::asTheDefaultOf($this->user, $this->vault, [
            ['type' => FieldTypeEnum::Email, 'label' => 'Email'],
        ]);

        $contact = new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: $this->card(),
            davUri: 'dwight.vcf',
        )->execute();

        $this->assertEquals('Dwight', $contact->first_name);
        $this->assertEquals('Schrute', $contact->last_name);
        $this->assertEquals('Beet farmer', $contact->nickname);
        $this->assertEquals($version->id, $contact->structure_version_id);
        $this->assertEquals('dwight.vcf', $contact->dav_uri);
    }

    #[Test]
    public function it_writes_the_values_the_structure_has_a_field_for(): void
    {
        $version = Structure::asTheDefaultOf($this->user, $this->vault, [
            ['type' => FieldTypeEnum::Email, 'label' => 'Email'],
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
            ['type' => FieldTypeEnum::Address, 'label' => 'Home'],
            ['type' => FieldTypeEnum::RecurringDate, 'key' => 'birthday', 'label' => 'Birthday'],
        ]);

        $contact = new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: $this->card(),
        )->execute();

        $this->assertEquals('dwight@dundermifflin.test', $this->valueOf($contact, $version, 0)->value_string);
        $this->assertEquals('+15705551212', $this->valueOf($contact, $version, 1)->value_string);
        $this->assertEquals([
            'po_box' => '',
            'extended' => '',
            'street' => '1725 Slough Avenue',
            'city' => 'Scranton',
            'region' => 'PA',
            'postal_code' => '18505',
            'country' => 'USA',
        ], $this->valueOf($contact, $version, 2)->value_json);
        $this->assertEquals('1984-01-20', $this->valueOf($contact, $version, 3)->value_date->format('Y-m-d'));
    }

    #[Test]
    public function it_writes_a_date_that_arrives_without_a_year_as_one_whose_year_is_unknown(): void
    {
        $version = Structure::asTheDefaultOf($this->user, $this->vault, [
            ['type' => FieldTypeEnum::RecurringDate, 'key' => 'birthday', 'label' => 'Birthday'],
        ]);

        $contact = new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nN:Schrute;Dwight;;;\r\nFN:Dwight Schrute\r\nBDAY:--0120\r\nEND:VCARD\r\n",
        )->execute();

        $value = $this->valueOf($contact, $version, 0);

        $this->assertTrue($value->year_is_unknown);
        $this->assertEquals('01-20', $value->value_date->format('m-d'));
    }

    #[Test]
    public function it_reads_the_type_a_card_carries_as_the_label_of_the_value(): void
    {
        $version = Structure::asTheDefaultOf($this->user, $this->vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Phone'],
        ]);
        $mobile = $this->labelOn($version, 0, 'mobile', 'Mobile');

        $contact = new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: $this->cardCarrying("TEL;TYPE=cell:+15705551212\r\n"),
            davUri: 'dwight.vcf',
        )->execute();

        $this->assertEquals($mobile->id, $this->valueOf($contact, $version, 0)->value_list_item_id);
    }

    #[Test]
    public function it_prefers_the_wording_a_device_wrote_over_the_type_beside_it(): void
    {
        $version = Structure::asTheDefaultOf($this->user, $this->vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Phone'],
        ]);
        $this->labelOn($version, 0, 'mobile', 'Mobile');
        $boat = $this->labelOn($version, 0, null, 'Boat');

        $contact = new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: $this->cardCarrying("item1.TEL;TYPE=cell:+15705551212\r\nitem1.X-ABLabel:Boat\r\n"),
            davUri: 'dwight.vcf',
        )->execute();

        $this->assertEquals($boat->id, $this->valueOf($contact, $version, 0)->value_list_item_id);
    }

    #[Test]
    public function it_reads_the_type_of_an_address_as_the_label_of_the_address(): void
    {
        $version = Structure::asTheDefaultOf($this->user, $this->vault, [
            ['type' => FieldTypeEnum::Address, 'label' => 'Address'],
        ]);
        $this->labelOn($version, 0, AddressLabelEnum::Home->value, 'Home');
        $work = $this->labelOn($version, 0, AddressLabelEnum::Work->value, 'Work');

        $contact = new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: $this->cardCarrying("ADR;TYPE=work:PO Box 12;Suite 3;1725 Slough Avenue;Scranton;PA;18505;Lackawanna\r\n"),
            davUri: 'dwight.vcf',
        )->execute();

        $stored = $this->valueOf($contact, $version, 0);

        $this->assertEquals($work->id, $stored->value_list_item_id);
        $this->assertEquals([
            'po_box' => 'PO Box 12',
            'extended' => 'Suite 3',
            'street' => '1725 Slough Avenue',
            'city' => 'Scranton',
            'region' => 'PA',
            'postal_code' => '18505',
            'country' => 'Lackawanna',
        ], $stored->value_json);
    }

    #[Test]
    public function it_prefers_the_wording_a_device_wrote_beside_an_address_over_its_type(): void
    {
        $version = Structure::asTheDefaultOf($this->user, $this->vault, [
            ['type' => FieldTypeEnum::Address, 'label' => 'Address'],
        ]);
        $this->labelOn($version, 0, AddressLabelEnum::Work->value, 'Work');
        $other = $this->labelOn($version, 0, AddressLabelEnum::Other->value, 'Other');

        $contact = new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: $this->cardCarrying("item1.ADR;TYPE=work:;;Schrute Farms;Honesdale;PA;18431;USA\r\nitem1.X-ABLabel:Other\r\n"),
            davUri: 'dwight.vcf',
        )->execute();

        $this->assertEquals($other->id, $this->valueOf($contact, $version, 0)->value_list_item_id);
    }

    #[Test]
    public function it_writes_no_label_for_a_wording_the_list_does_not_hold(): void
    {
        $version = Structure::asTheDefaultOf($this->user, $this->vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Phone'],
        ]);
        $this->labelOn($version, 0, 'mobile', 'Mobile');

        $contact = new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: $this->cardCarrying("item1.TEL:+15705551212\r\nitem1.X-ABLabel:Submarine\r\n"),
            davUri: 'dwight.vcf',
        )->execute();

        $value = $this->valueOf($contact, $version, 0);

        $this->assertNull($value->value_list_item_id);
        $this->assertEquals('+15705551212', $value->value_string);
    }

    #[Test]
    public function it_keeps_an_unmatched_wording_against_the_open_label(): void
    {
        $version = Structure::asTheDefaultOf($this->user, $this->vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Phone'],
        ]);
        $this->labelOn($version, 0, 'mobile', 'Mobile');
        $open = $this->labelOn($version, 0, ValueListItem::OPEN_KEY, 'Custom...');

        $contact = new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: $this->cardCarrying("item1.TEL:+15705551212\r\nitem1.X-ABLabel:Submarine\r\n"),
            davUri: 'dwight.vcf',
        )->execute();

        $value = $this->valueOf($contact, $version, 0);

        $this->assertEquals($open->id, $value->value_list_item_id);
        $this->assertEquals('Submarine', $value->label_wording);
    }

    #[Test]
    public function it_adds_nothing_to_a_list_the_account_owns(): void
    {
        $version = Structure::asTheDefaultOf($this->user, $this->vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Phone'],
        ]);
        $mobile = $this->labelOn($version, 0, 'mobile', 'Mobile');

        new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: $this->cardCarrying("item1.TEL:+15705551212\r\nitem1.X-ABLabel:Submarine\r\n"),
            davUri: 'dwight.vcf',
        )->execute();

        $this->assertEquals(1, ValueListItem::query()->where('value_list_id', $mobile->value_list_id)->count());
    }

    #[Test]
    public function it_keeps_the_whole_card_the_device_sent(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
        ]);

        $contact = new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: $this->card(),
        )->execute();

        $this->assertStringContainsString('ORG:Schrute Farms', (string) $contact->vcard);
        $this->assertStringContainsString('X-DEVICE-INVENTION:beets', (string) $contact->vcard);
    }

    #[Test]
    public function it_clears_a_field_whose_property_the_card_no_longer_carries(): void
    {
        $version = Structure::asTheDefaultOf($this->user, $this->vault, [
            ['type' => FieldTypeEnum::Email, 'label' => 'Email'],
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $this->vault->id,
            'account_id' => $this->vault->account_id,
            'structure_version_id' => $version->id,
        ]);
        $this->write($contact, $version, 0, ['value_string' => 'dwight@dundermifflin.test']);

        new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nN:Schrute;Dwight;;;\r\nFN:Dwight Schrute\r\nEND:VCARD\r\n",
            contact: $contact,
        )->execute();

        $this->assertDatabaseMissing('field_values', [
            'valuable_id' => $contact->id,
            'value_string' => 'dwight@dundermifflin.test',
        ]);
    }

    #[Test]
    public function it_leaves_a_sensitive_field_as_it_was_when_the_key_cannot_reach_it(): void
    {
        $version = Structure::asTheDefaultOf($this->user, $this->vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Private line', 'is_sensitive' => true],
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $this->vault->id,
            'account_id' => $this->vault->account_id,
            'structure_version_id' => $version->id,
        ]);
        $this->write($contact, $version, 0, ['value_string' => '+15705559999']);

        new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: $this->card(),
            reachesSensitiveFields: false,
            contact: $contact,
        )->execute();

        $this->assertEquals('+15705559999', $this->valueOf($contact, $version, 0)->value_string);
    }

    #[Test]
    public function it_writes_a_sensitive_field_when_the_key_reaches_it(): void
    {
        $version = Structure::asTheDefaultOf($this->user, $this->vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Private line', 'is_sensitive' => true],
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $this->vault->id,
            'account_id' => $this->vault->account_id,
            'structure_version_id' => $version->id,
        ]);
        $this->write($contact, $version, 0, ['value_string' => '+15705559999']);

        new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: $this->card(),
            reachesSensitiveFields: true,
            contact: $contact,
        )->execute();

        $this->assertEquals('+15705551212', $this->valueOf($contact, $version, 0)->value_string);
    }

    #[Test]
    public function it_leaves_a_value_the_structure_has_no_room_for_on_the_card_alone(): void
    {
        $version = Structure::asTheDefaultOf($this->user, $this->vault, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
        ]);

        $contact = new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nN:Schrute;Dwight;;;\r\nFN:Dwight Schrute\r\nTEL:+15705551212\r\nTEL:+15705550100\r\nTEL:+15705550200\r\nEND:VCARD\r\n",
        )->execute();

        $this->assertEquals('+15705551212', $this->valueOf($contact, $version, 0)->value_string);
        $this->assertDatabaseCount('field_values', 1);
        $this->assertStringContainsString('+15705550100', (string) $contact->vcard);
        $this->assertStringContainsString('+15705550200', (string) $contact->vcard);
    }

    #[Test]
    public function it_writes_one_email_address_and_one_phone_number_on_the_default_template(): void
    {
        new CreateDefaultTemplate(user: $this->user)->handle();

        $contact = $this->importCarrying("EMAIL:dwight@dundermifflin.test\r\nTEL:+15705551212\r\n");

        $this->assertSame(['dwight@dundermifflin.test'], $this->writtenIn($contact, 'email')->pluck('value')->all());
        $this->assertSame(['+15705551212'], $this->writtenIn($contact, 'phone')->pluck('value')->all());
    }

    #[Test]
    public function it_writes_every_email_address_phone_number_and_address_in_the_order_of_the_card(): void
    {
        new CreateDefaultValueLists(user: $this->user)->execute();
        new CreateDefaultTemplate(user: $this->user)->handle();

        $contact = $this->importCarrying(
            "EMAIL;TYPE=work:dwight@dundermifflin.test\r\nEMAIL;TYPE=home:dwight@schrutefarms.test\r\n"
            ."TEL;TYPE=work:+15705551212\r\nTEL;TYPE=home:+15705550100\r\n"
            ."ADR;TYPE=work:;;1725 Slough Avenue;Scranton;PA;18505;USA\r\nADR;TYPE=home:;;Schrute Farms;Honesdale;PA;18431;USA\r\n",
        );

        $emails = $this->writtenIn($contact, 'email');
        $this->assertSame(['dwight@dundermifflin.test', 'dwight@schrutefarms.test'], $emails->pluck('value')->all());
        $this->assertSame(['work', 'home'], $emails->map(fn (FieldValue $value): ?string => $value->valueListItem?->key)->all());
        $this->assertSame(['+15705551212', '+15705550100'], $this->writtenIn($contact, 'phone')->pluck('value')->all());
        $this->assertSame(['1725 Slough Avenue', 'Schrute Farms'], $this->writtenIn($contact, 'address')->map(fn (FieldValue $value): string => $value->value['street'])->all());
    }

    #[Test]
    public function it_removes_the_repetition_of_a_phone_number_the_card_no_longer_carries(): void
    {
        new CreateDefaultTemplate(user: $this->user)->handle();
        $contact = $this->importCarrying("TEL:+15705551212\r\nTEL:+15705550100\r\nTEL:+15705550200\r\n");

        $this->importCarrying("TEL:+15705551212\r\nTEL:+15705550200\r\n", $contact);

        $this->assertSame(['+15705551212', '+15705550200'], $this->writtenIn($contact, 'phone')->pluck('value')->all());
        $this->assertSame(2, $contact->fieldValueRows()->whereHas('field', fn ($query) => $query->where('key', 'phones'))->count());
    }

    #[Test]
    public function it_leaves_on_the_card_an_email_address_its_repeater_has_no_room_for(): void
    {
        new CreateDefaultTemplate(user: $this->user)->handle();
        Field::query()->where('account_id', $this->user->account_id)->where('key', 'emails')->update(['repeat_max' => 1]);

        $contact = $this->importCarrying("EMAIL:dwight@dundermifflin.test\r\nEMAIL:dwight@schrutefarms.test\r\n");

        $this->assertSame(['dwight@dundermifflin.test'], $this->writtenIn($contact, 'email')->pluck('value')->all());
        $this->assertStringContainsString('dwight@schrutefarms.test', (string) $contact->vcard);
    }

    #[Test]
    public function it_refuses_a_card_carrying_no_name_at_all(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);

        $this->expectException(BadRequest::class);

        new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:nobody\r\nFN: \r\nN:;;;;\r\nEND:VCARD\r\n",
        )->execute();
    }

    #[Test]
    public function it_names_a_contact_with_no_name_after_its_organisation(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);

        $contact = $this->importNameless("ORG:Dunder Mifflin;Scranton\r\nNICKNAME:Paper people\r\nEMAIL:info@dundermifflin.test\r\n");

        $this->assertSame('Dunder Mifflin', $contact->name);
    }

    #[Test]
    public function it_names_a_contact_with_no_name_and_no_organisation_after_its_nickname(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);

        $contact = $this->importNameless("NICKNAME:Big Tuna\r\nEMAIL:jim@dundermifflin.test\r\n");

        $this->assertSame('Big Tuna', $contact->name);
    }

    #[Test]
    public function it_names_a_contact_after_its_email_address_before_its_phone_number(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);

        $contact = $this->importNameless("TEL:+15705550100\r\nEMAIL:jim@dundermifflin.test\r\n");

        $this->assertSame('jim@dundermifflin.test', $contact->name);
    }

    #[Test]
    public function it_names_a_contact_holding_only_a_phone_number_and_an_empty_name_after_the_number(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);

        $contact = $this->importNameless("FN: \r\nN:;;;;\r\nTEL:+15705550100\r\n");

        $this->assertSame('+15705550100', $contact->name);
    }

    #[Test]
    public function it_adds_no_name_part_to_the_card_of_a_contact_named_after_its_organisation(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);

        $contact = $this->importNameless("ORG:Dunder Mifflin\r\n");

        $card = Reader::read(new VCardMerger()->merge($contact));
        $this->assertSame(['', '', '', '', ''], $card->N->getParts());
        $this->assertSame('Dunder Mifflin', (string) $card->FN);
    }

    #[Test]
    public function it_takes_a_name_from_the_full_name_when_the_card_carries_no_parts(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);

        $contact = new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:mose\r\nFN:Mose Schrute\r\nEND:VCARD\r\n",
        )->execute();

        $this->assertSame('Mose Schrute', $contact->formatted_name);
        $this->assertSame('Mose Schrute', $contact->name);
    }

    #[Test]
    public function it_refuses_a_body_that_is_not_a_card(): void
    {
        $this->expectException(BadRequest::class);

        new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: 'beets',
        )->execute();
    }

    #[Test]
    public function it_leaves_the_avatar_as_it_is_while_file_upload_is_off_for_the_account(): void
    {
        Queue::fake();
        Storage::fake('local');
        Structure::asTheDefaultOf($this->user, $this->vault, []);
        $photo = UploadedFile::fake()->image('dwight.jpg', 200, 200)->getContent();

        $contact = new ImportVCard(user: $this->user, vault: $this->vault, card: $this->card())->execute();
        new UpdateContactAvatar(
            user: $this->user,
            vault: $this->vault,
            contact: $contact,
            upload: UploadedFile::fake()->image('beets.jpg', 200, 200),
        )->execute();
        $avatarId = $contact->refresh()->avatar_file_id;

        $this->user->account->features()->deactivate('file-upload');
        Feature::flushCache();

        new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: $this->cardCarrying('PHOTO:data:image/jpeg;base64,'.base64_encode($photo)."\r\n"),
            contact: $contact,
        )->execute();
        $this->assertSame($avatarId, $contact->refresh()->avatar_file_id);

        new ImportVCard(user: $this->user, vault: $this->vault, card: $this->card(), contact: $contact)->execute();
        $this->assertSame($avatarId, $contact->refresh()->avatar_file_id);
    }

    private function labelOn(StructureVersion $version, int $index, ?string $key, string $label): ValueListItem
    {
        $field = $this->fieldsOf($version)[$index];

        if ($field->value_list_id === null) {
            $field->value_list_id = ValueList::factory()->create(['account_id' => $field->account_id])->id;
            $field->save();
        }

        return ValueListItem::factory()->create([
            'account_id' => $field->account_id,
            'value_list_id' => $field->value_list_id,
            'key' => $key,
            'label' => $label,
        ]);
    }

    #[Test]
    public function it_reads_the_categories_a_card_carries_and_adds_the_ones_the_list_lacks(): void
    {
        [$version, $list] = $this->categoriesStructure();
        $sales = ValueListItem::factory()->create(['account_id' => $this->user->account_id, 'value_list_id' => $list->id, 'label' => 'Sales', 'position' => 1]);

        $contact = $this->importCarrying("CATEGORIES:sales,Finer Things Club\r\n");

        $finerThings = ValueListItem::query()->where('value_list_id', $list->id)->where('label', 'Finer Things Club')->sole();
        $this->assertSame([$sales->id, $finerThings->id], $this->valueOf($contact, $version, 0)->value_list_item_ids);
    }

    #[Test]
    public function it_skips_a_category_no_longer_offered_and_keeps_the_others(): void
    {
        [$version, $list] = $this->categoriesStructure();
        $sales = ValueListItem::factory()->create(['account_id' => $this->user->account_id, 'value_list_id' => $list->id, 'label' => 'Sales']);
        ValueListItem::factory()->disabled()->create(['account_id' => $this->user->account_id, 'value_list_id' => $list->id, 'label' => 'Party Planning Committee']);

        $contact = $this->importCarrying("CATEGORIES:Party Planning Committee,Sales\r\n");

        $this->assertSame([$sales->id], $this->valueOf($contact, $version, 0)->value_list_item_ids);
        $this->assertSame(2, ValueListItem::query()->where('value_list_id', $list->id)->count());
    }

    #[Test]
    public function it_empties_the_categories_of_a_contact_whose_card_carries_none(): void
    {
        [$version, $list] = $this->categoriesStructure();
        $sales = ValueListItem::factory()->create(['account_id' => $this->user->account_id, 'value_list_id' => $list->id, 'label' => 'Sales']);
        $contact = $this->importCarrying("CATEGORIES:Sales\r\n");

        $this->importCarrying('', $contact);

        $this->assertSame(0, FieldValue::query()->where('valuable_id', $contact->id)->where('field_id', $this->fieldsOf($version)[0]->id)->count());
        $this->assertModelExists($sales);
    }

    #[Test]
    public function it_keeps_the_categories_on_the_card_when_the_structure_holds_none(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault, [['type' => FieldTypeEnum::Phone, 'label' => 'Phone']]);

        $contact = $this->importCarrying("CATEGORIES:Golf\r\n");

        $this->assertStringContainsString('CATEGORIES:Golf', (string) $contact->vcard);
        $this->assertSame(0, ValueListItem::query()->where('account_id', $this->user->account_id)->count());
    }

    /** @return array{0: StructureVersion, 1: ValueList} */
    private function categoriesStructure(): array
    {
        $version = Structure::asTheDefaultOf($this->user, $this->vault, [['type' => FieldTypeEnum::Multiselect, 'label' => 'Category']]);
        $list = ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $this->user->account_id]);
        $this->fieldsOf($version)[0]->update(['value_list_id' => $list->id]);

        return [$version, $list];
    }

    private function cardCarrying(string $property): string
    {
        return "BEGIN:VCARD\r\n".
            "VERSION:4.0\r\n".
            "UID:dwight\r\n".
            "N:Schrute;Dwight;;;\r\n".
            "FN:Dwight Schrute\r\n".
            $property.
            "END:VCARD\r\n";
    }

    private function importCarrying(string $properties, ?Contact $contact = null): Contact
    {
        return new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: $this->cardCarrying($properties),
            contact: $contact,
        )->execute();
    }

    private function importNameless(string $properties): Contact
    {
        return new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:nobody\r\n{$properties}END:VCARD\r\n",
        )->execute();
    }

    /** @return Collection<int, FieldValue> */
    private function writtenIn(Contact $contact, string $key): Collection
    {
        return $contact->fieldValues()
            ->with(['field', 'valueListItem', 'row'])
            ->get()
            ->filter(fn (FieldValue $value): bool => $value->field->key === $key)
            ->sortBy(fn (FieldValue $value): int => $value->row->position ?? 0)
            ->values();
    }

    private function card(): string
    {
        return "BEGIN:VCARD\r\n".
            "VERSION:4.0\r\n".
            "UID:dwight\r\n".
            "N:Schrute;Dwight;;;\r\n".
            "FN:Dwight Schrute\r\n".
            "NICKNAME:Beet farmer\r\n".
            "ORG:Schrute Farms\r\n".
            "EMAIL:dwight@dundermifflin.test\r\n".
            "TEL:+15705551212\r\n".
            "ADR:;;1725 Slough Avenue;Scranton;PA;18505;USA\r\n".
            "BDAY:19840120\r\n".
            "X-DEVICE-INVENTION:beets\r\n".
            "END:VCARD\r\n";
    }

    /** @param  array<string, mixed>  $attributes */
    private function write(Contact $contact, StructureVersion $version, int $index, array $attributes): FieldValue
    {
        $field = $this->fieldsOf($version)[$index];

        return FieldValue::factory()->create([
            ...$attributes,
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field->id,
            'field_key' => $field->key,
        ]);
    }

    private function valueOf(Contact $contact, StructureVersion $version, int $index): FieldValue
    {
        return FieldValue::query()
            ->where('valuable_id', $contact->id)
            ->where('field_id', $this->fieldsOf($version)[$index]->id)
            ->firstOrFail();
    }

    /** @return array<int, Field> */
    private function fieldsOf(StructureVersion $version): array
    {
        return $version->sectionVersions()->with('fields')->get()
            ->flatMap(fn ($section) => $section->fields)
            ->values()
            ->all();
    }

    #[Test]
    public function it_creates_one_contact_holding_every_way_a_card_writes_the_name(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);

        $contact = new ImportVCard(user: $this->user, vault: $this->vault, card: $this->multilingualCard())->execute();

        $this->assertSame(1, Contact::query()->count());
        $this->assertSame(4, $contact->nameRepresentations()->count());
        $this->assertSame('김민지', $contact->name);
        $this->assertSame('Minnie', $contact->nickname);
        $this->assertSame(ContactNameKindEnum::Phonetic, $contact->nameFor(kind: ContactNameKindEnum::Phonetic)?->kind);
        $this->assertSame('Kim Min-ji', $contact->nameFor(kind: ContactNameKindEnum::Transliteration)?->displayText());
    }

    #[Test]
    public function it_shows_the_first_written_name_when_no_full_name_is_preferred(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);
        $card = str_replace(';PREF=1', '', $this->multilingualCard());
        $card = str_replace("N;ALTID=1;LANGUAGE=ko;SCRIPT=Latn;PHONETIC=x-rr:Gim;Minji;;;\r\n", '', $card);
        $card = str_replace("BEGIN:VCARD\r\nVERSION:4.0\r\n", "BEGIN:VCARD\r\nVERSION:4.0\r\nN;ALTID=1;LANGUAGE=ko;SCRIPT=Latn;PHONETIC=x-rr:Gim;Minji;;;\r\n", $card);

        $contact = new ImportVCard(user: $this->user, vault: $this->vault, card: $card)->execute();

        $this->assertSame('김민지', $contact->name);
    }

    #[Test]
    public function it_reconciles_every_way_of_writing_the_name_from_a_card_carrying_several(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);
        $contact = new ImportVCard(user: $this->user, vault: $this->vault, card: $this->multilingualCard())->execute();
        $exported = new VCardMerger()->merge($contact);
        $hanja = $contact->nameRepresentations()->where('script_code', 'Hani')->sole();
        $card = Reader::read($exported);

        foreach ([...$card->select('N'), ...$card->select('FN')] as $property) {
            if ((string) $property['PROP-ID'] === $hanja->uuid) {
                $card->remove($property);
            }
        }

        $renamed = str_replace(['Min-ji', 'Kim Min-ji'], ['Minji', 'Kim Minji'], $card->serialize());

        new ImportVCard(user: $this->user, vault: $this->vault, card: $renamed, contact: $contact->refresh())->execute();

        $this->assertModelMissing($hanja);
        $this->assertSame(3, $contact->nameRepresentations()->count());
        $this->assertSame('Kim Minji', $contact->nameFor(kind: ContactNameKindEnum::Transliteration)?->displayText());
        $this->assertSame('김민지', $contact->refresh()->name);
    }

    #[Test]
    public function it_changes_only_the_displayed_name_from_a_device_that_writes_one(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);
        $contact = new ImportVCard(user: $this->user, vault: $this->vault, card: $this->multilingualCard())->execute();

        new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: (string) file_get_contents(base_path('tests/Fixtures/Dav/single-name-client.vcf')),
            contact: $contact,
        )->execute();

        $this->assertSame(4, $contact->nameRepresentations()->count());
        $this->assertSame('Minnie Kim', $contact->refresh()->name);
        $this->assertSame('Kim Min-ji', $contact->nameFor(kind: ContactNameKindEnum::Transliteration)?->displayText());
    }

    #[Test]
    public function it_changes_only_the_name_a_lone_property_identifier_points_at(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);
        $contact = new ImportVCard(user: $this->user, vault: $this->vault, card: $this->multilingualCard())->execute();
        $latin = $contact->nameFor(kind: ContactNameKindEnum::Transliteration);

        new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:kim-min-ji\r\nN;PROP-ID={$latin?->uuid}:Kim;Minji;;;\r\nFN;PROP-ID={$latin?->uuid}:Kim Minji\r\nEND:VCARD\r\n",
            contact: $contact,
        )->execute();

        $this->assertSame(4, $contact->nameRepresentations()->count());
        $this->assertSame('Minji', $latin?->refresh()->given_name);
        $this->assertSame('김민지', $contact->refresh()->name);
    }

    #[Test]
    public function it_keeps_every_other_name_when_a_card_holds_one_name_and_several_full_names(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);
        $contact = new ImportVCard(user: $this->user, vault: $this->vault, card: $this->multilingualCard())->execute();

        new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:kim-min-ji\r\nN:Kim;Minnie;;;\r\nFN:Minnie Kim\r\nFN;LANGUAGE=ja:キム・ミンジ\r\nEND:VCARD\r\n",
            contact: $contact,
        )->execute();

        $this->assertSame(4, $contact->nameRepresentations()->count());
        $this->assertSame('Minnie Kim', $contact->refresh()->name);
        $this->assertSame('Kim Min-ji', $contact->nameFor(kind: ContactNameKindEnum::Transliteration)?->displayText());
    }

    #[Test]
    public function it_carries_the_order_a_single_name_is_written_in(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);
        $contact = new ImportVCard(user: $this->user, vault: $this->vault, card: "BEGIN:VCARD\r\nVERSION:4.0\r\nN:Scott;Michael;;;\r\nFN:Michael Scott\r\nEND:VCARD\r\n")->execute();

        new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: "BEGIN:VCARD\r\nVERSION:4.0\r\nN:Scott;Michael;;;\r\nFN:Scott Michael\r\nEND:VCARD\r\n",
            contact: $contact,
        )->execute();

        $this->assertSame(NameOrderEnum::FamilyFirst, $contact->refresh()->displayNameRepresentation?->name_order);
    }

    #[Test]
    public function it_never_shows_a_contact_under_a_pronunciation(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);
        $contact = new ImportVCard(user: $this->user, vault: $this->vault, card: $this->multilingualCard())->execute();
        $shown = $contact->displayNameRepresentation;

        new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:kim-min-ji\r\nN;PROP-ID={$shown?->uuid};PHONETIC=x-rr:Gim;Minji;;;\r\nN;SCRIPT=Latn;X-NAME-KIND=transliteration:Kim;Minji;;;\r\nEND:VCARD\r\n",
            contact: $contact,
        )->execute();

        $this->assertSame(ContactNameKindEnum::Phonetic, $shown?->refresh()->kind);
        $this->assertSame(ContactNameKindEnum::Transliteration, $contact->refresh()->displayNameRepresentation?->kind);
    }

    #[Test]
    public function it_refuses_a_card_whose_only_name_is_a_pronunciation(): void
    {
        $this->expectException(BadRequest::class);

        new ImportVCard(user: $this->user, vault: $this->vault, card: "BEGIN:VCARD\r\nVERSION:4.0\r\nN;PHONETIC=ipa:ʃruːt;ˈdwaɪt;;;\r\nEND:VCARD\r\n")->execute();
    }

    #[Test]
    public function it_matches_each_name_once_when_a_card_repeats_a_property_identifier(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);
        $contact = new ImportVCard(user: $this->user, vault: $this->vault, card: $this->multilingualCard())->execute();
        $shown = $contact->displayNameRepresentation;

        new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:kim-min-ji\r\nN;PROP-ID={$shown?->uuid}:김;민지;;;\r\nFN;PROP-ID={$shown?->uuid};PREF=1:김민지\r\nN;PROP-ID={$shown?->uuid};SCRIPT=Latn:Kim;Minji;;;\r\nEND:VCARD\r\n",
            contact: $contact,
        )->execute();

        $this->assertSame(2, $contact->nameRepresentations()->count());
        $this->assertModelExists($shown);
        $this->assertSame('김민지', $contact->refresh()->name);
    }

    #[Test]
    public function it_cuts_a_name_too_long_for_the_record(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);
        $long = str_repeat('Schrute', 50);

        $contact = new ImportVCard(user: $this->user, vault: $this->vault, card: "BEGIN:VCARD\r\nVERSION:4.0\r\nN;ALTID={$long}:{$long};Dwight;;;\r\nFN;ALTID={$long}:Dwight\r\nEND:VCARD\r\n")->execute();
        $shown = $contact->displayNameRepresentation;

        $this->assertSame(255, mb_strlen((string) $shown?->family_name));
        $this->assertSame(64, mb_strlen((string) $shown?->vcard_alt_id));
    }

    #[Test]
    public function it_keeps_what_it_does_not_manage_on_the_kept_card(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);

        $contact = new ImportVCard(user: $this->user, vault: $this->vault, card: $this->multilingualCard())->execute();

        $this->assertStringContainsString('X-ORIGIN=family-register', (string) $contact->vcard);
        $this->assertStringContainsString('SORT-AS=Kim', (string) $contact->vcard);
    }

    private function multilingualCard(): string
    {
        return (string) file_get_contents(base_path('tests/Fixtures/Dav/multilingual-names.vcf'));
    }
}
