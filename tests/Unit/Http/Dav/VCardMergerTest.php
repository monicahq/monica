<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Dav;

use App\Actions\ImportVCard;
use App\Enums\AddressLabelEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\ValueListEnum;
use App\Http\Dav\VCardMerger;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Document;
use Sabre\VObject\Reader;
use Tests\Fixtures\Dav\Structure;
use Tests\TestCase;

class VCardMergerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_name_of_a_contact_no_device_has_ever_written(): void
    {
        $contact = $this->contact(['first_name' => 'Dwight', 'last_name' => 'Schrute']);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('VERSION:4.0', $card);
        $this->assertStringContainsString('N:Schrute;Dwight;;;', $card);
        $this->assertStringContainsString('FN:Dwight Schrute', $card);
        $this->assertStringContainsString('UID:'.$contact->uuid, $card);
        $this->assertStringContainsString('REV:', $card);
    }

    #[Test]
    public function it_writes_every_part_of_the_name_in_its_own_slot(): void
    {
        $contact = $this->contact([
            'prefix' => 'Dr',
            'first_name' => 'Dwight',
            'middle_name' => 'Kurt',
            'last_name' => 'Schrute',
            'suffix' => 'III',
            'nickname' => 'Beet farmer',
        ]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('N:Schrute;Dwight;Kurt;Dr;III', $card);
        $this->assertStringContainsString('NICKNAME:Beet farmer', $card);
    }

    #[Test]
    public function it_writes_a_value_the_record_holds(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Work'],
        ]);
        $contact = $this->contact(['structure_version_id' => $version->id]);
        $this->value($contact, $version, 0, ['value_string' => '+15705551212']);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('TEL:+15705551212', $card);
    }

    #[Test]
    public function it_writes_a_standard_label_as_the_type_every_address_book_reads(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Phone'],
        ]);
        $contact = $this->contact(['structure_version_id' => $version->id]);
        $mobile = $this->labelOn($version, 0, 'mobile', 'Mobile');
        $this->value($contact, $version, 0, ['value_string' => '+15705551212', 'value_list_item_id' => $mobile->id]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('TYPE=cell', $card);
        $this->assertStringContainsString('+15705551212', $card);
        $this->assertStringNotContainsString('X-ABLabel:Phone', $card);
    }

    #[Test]
    public function it_writes_a_label_nobody_standardised_as_the_wording_the_account_chose(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Phone'],
        ]);
        $contact = $this->contact(['structure_version_id' => $version->id]);
        $boat = $this->labelOn($version, 0, null, 'Boat');
        $this->value($contact, $version, 0, ['value_string' => '+15705551212', 'value_list_item_id' => $boat->id]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('X-ABLabel:Boat', $card);
        $this->assertStringNotContainsString('X-ABLabel:Phone', $card);
    }

    #[Test]
    public function it_writes_the_wording_somebody_typed_rather_than_the_open_label(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Phone'],
        ]);
        $contact = $this->contact(['structure_version_id' => $version->id]);
        $open = $this->labelOn($version, 0, ValueListItem::OPEN_KEY, 'Custom...');
        $this->value($contact, $version, 0, [
            'value_string' => '+15705551212',
            'value_list_item_id' => $open->id,
            'label_wording' => 'Boat',
        ]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('X-ABLabel:Boat', $card);
        $this->assertStringNotContainsString('X-ABLabel:Other', $card);
    }

    #[Test]
    public function it_writes_no_label_beside_a_phone_number_that_carries_none(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Work'],
        ]);
        $contact = $this->contact(['structure_version_id' => $version->id]);
        $this->value($contact, $version, 0, ['value_string' => '+15705551212']);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString("\r\nTEL:+15705551212\r\n", $card);
        $this->assertStringNotContainsString('X-ABLabel', $card);
    }

    #[Test]
    public function it_keeps_the_label_a_device_wrote_beside_a_value_that_carries_none(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Work'],
        ]);
        $contact = $this->contact([
            'structure_version_id' => $version->id,
            'vcard' => "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nFN:Dwight Schrute\r\nitem1.TEL:+15705551212\r\nitem1.X-ABLabel:Boat\r\nEND:VCARD\r\n",
        ]);
        $this->value($contact, $version, 0, ['value_string' => '+15705551212']);

        $this->assertMatchesRegularExpression('/^item1\.X-ABLabel:Boat\r$/im', new VCardMerger()->merge($contact));
    }

    #[Test]
    public function it_writes_no_label_for_a_sensitive_field_because_it_writes_no_property_at_all(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Phone', 'is_sensitive' => true],
        ]);
        $contact = $this->contact(['structure_version_id' => $version->id]);
        $mobile = $this->labelOn($version, 0, 'mobile', 'Mobile');
        $this->value($contact, $version, 0, ['value_string' => '+15705551212', 'value_list_item_id' => $mobile->id]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringNotContainsString('+15705551212', $card);
        $this->assertStringNotContainsString('X-ABLabel:Mobile', $card);
    }

    #[Test]
    public function it_labels_a_property_the_device_already_put_on_the_card(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Phone'],
        ]);
        $contact = $this->contact([
            'structure_version_id' => $version->id,
            'vcard' => "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nFN:Dwight Schrute\r\nTEL:+15705550000\r\nEND:VCARD\r\n",
        ]);
        $mobile = $this->labelOn($version, 0, 'mobile', 'Mobile');
        $this->value($contact, $version, 0, ['value_string' => '+15705551212', 'value_list_item_id' => $mobile->id]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('TYPE=cell', $card);
        $this->assertStringContainsString('+15705551212', $card);
    }

    #[Test]
    public function it_keeps_what_the_structure_has_no_field_for(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Work'],
        ]);
        $contact = $this->contact([
            'structure_version_id' => $version->id,
            'vcard' => "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nFN:Dwight Schrute\r\nORG:Schrute Farms\r\nTEL:+15705550000\r\nTEL;TYPE=work:+15705550100\r\nEND:VCARD\r\n",
        ]);
        $this->value($contact, $version, 0, ['value_string' => '+15705551212']);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('ORG:Schrute Farms', $card);
        $this->assertStringContainsString('TEL:+15705551212', $card);
        $this->assertStringContainsString('+15705550100', $card);
        $this->assertStringNotContainsString('+15705550000', $card);
        $this->assertStringContainsString('UID:dwight', $card);
    }

    #[Test]
    public function it_leaves_a_surplus_value_the_structure_has_no_room_for_untouched(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Work'],
        ]);
        $contact = $this->contact([
            'structure_version_id' => $version->id,
            'vcard' => "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nFN:Dwight Schrute\r\nTEL;TYPE=cell:+15705550000\r\nTEL;TYPE=home:+15705550200\r\nTEL;TYPE=work:+15705550300\r\nEND:VCARD\r\n",
        ]);
        $this->value($contact, $version, 0, ['value_string' => '+15705551212']);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('+15705551212', $card);
        $this->assertStringContainsString('+15705550200', $card);
        $this->assertStringContainsString('+15705550300', $card);
        $this->assertStringNotContainsString('+15705550000', $card);
    }

    #[Test]
    public function it_leaves_a_property_no_value_claims_on_the_card(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Email, 'label' => 'Email'],
        ]);
        $contact = $this->contact([
            'structure_version_id' => $version->id,
            'vcard' => "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nFN:Dwight Schrute\r\nEMAIL:dwight@dundermifflin.test\r\nEND:VCARD\r\n",
        ]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('dwight@dundermifflin.test', $card);
    }

    #[Test]
    public function it_writes_a_birthday_without_a_year_when_the_year_is_unknown(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::RecurringDate, 'key' => 'birthday', 'label' => 'Birthday'],
        ]);
        $contact = $this->contact(['structure_version_id' => $version->id]);
        $this->value($contact, $version, 0, [
            'value_string' => null,
            'value_date' => '1900-02-03',
            'year_is_unknown' => true,
        ]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('BDAY:--0203', $card);
    }

    #[Test]
    public function it_writes_a_birthday_with_its_year_when_the_year_is_known(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::RecurringDate, 'key' => 'birthday', 'label' => 'Birthday'],
        ]);
        $contact = $this->contact(['structure_version_id' => $version->id]);
        $this->value($contact, $version, 0, [
            'value_string' => null,
            'value_date' => '1984-02-03',
        ]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('BDAY:1984-02-03', $card);
    }

    #[Test]
    public function it_writes_a_recurring_date_that_is_not_a_birthday_as_its_own_property(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::RecurringDate, 'key' => 'met-on', 'label' => 'The day we met'],
        ]);
        $contact = $this->contact(['structure_version_id' => $version->id]);
        $this->value($contact, $version, 0, [
            'value_string' => null,
            'value_date' => '2005-03-24',
        ]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('X-ABDATE:2005-03-24', $card);
        $this->assertStringContainsString('X-ABLabel:The day we met', $card);
    }

    #[Test]
    public function it_writes_an_address_marked_home_or_work_as_the_type_a_card_knows(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Address, 'label' => 'Address'],
        ]);
        $contact = $this->contact(['structure_version_id' => $version->id]);
        $home = $this->labelOn($version, 0, AddressLabelEnum::Home->value, 'Home');
        $work = ValueListItem::factory()->create([
            'account_id' => $home->account_id,
            'value_list_id' => $home->value_list_id,
            'key' => AddressLabelEnum::Work->value,
            'label' => 'Work',
        ]);
        $value = $this->value($contact, $version, 0, [
            'value_string' => null,
            'value_json' => $this->slough(),
            'value_list_item_id' => $home->id,
        ]);

        $value->update(['value_list_item_id' => $work->id]);

        $card = new VCardMerger()->merge($contact->fresh());

        $this->assertStringNotContainsString('X-ABLabel', $card);
    }

    #[Test]
    public function it_writes_an_address_label_no_card_standardised_as_its_wording(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Address, 'label' => 'Address'],
        ]);
        $contact = $this->contact(['structure_version_id' => $version->id]);
        $other = $this->labelOn($version, 0, AddressLabelEnum::Other->value, 'Other');
        $this->value($contact, $version, 0, [
            'value_string' => null,
            'value_json' => $this->slough(),
            'value_list_item_id' => $other->id,
        ]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('item1.X-ABLabel:Other', $card);
        $this->assertStringNotContainsString('TYPE=', $card);
    }

    #[Test]
    public function it_writes_no_label_beside_an_address_nobody_labelled(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Address, 'label' => 'Branch'],
        ]);
        $contact = $this->contact(['structure_version_id' => $version->id]);
        $this->value($contact, $version, 0, ['value_string' => null, 'value_json' => $this->slough()]);

        $this->assertStringNotContainsString('X-ABLabel', new VCardMerger()->merge($contact));
    }

    #[Test]
    public function it_leaves_a_sensitive_field_out_of_the_card_of_a_key_that_cannot_reach_it(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Private line', 'is_sensitive' => true],
        ]);
        $contact = $this->contact(['structure_version_id' => $version->id]);
        $this->value($contact, $version, 0, ['value_string' => '+15705559999']);

        $this->assertStringNotContainsString('+15705559999', new VCardMerger()->merge($contact));
        $this->assertStringContainsString('+15705559999', new VCardMerger(reachesSensitiveFields: true)->merge($contact));
    }

    #[Test]
    public function it_leaves_a_sensitive_property_on_the_card_exactly_as_the_device_sent_it(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Private line', 'is_sensitive' => true],
        ]);
        $contact = $this->contact([
            'structure_version_id' => $version->id,
            'vcard' => "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nFN:Dwight Schrute\r\nTEL:+15705550000\r\nEND:VCARD\r\n",
        ]);
        $this->value($contact, $version, 0, ['value_string' => '+15705559999']);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('+15705550000', $card);
        $this->assertStringNotContainsString('+15705559999', $card);
    }

    #[Test]
    public function it_withholds_a_sensitive_value_a_card_stored_by_another_key_carries(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Private line', 'is_sensitive' => true],
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
        ]);
        $contact = $this->contact([
            'structure_version_id' => $version->id,
            'vcard' => "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nFN:Dwight Schrute\r\nTEL:+15705559999\r\nTEL:+15705551212\r\nEND:VCARD\r\n",
        ]);
        $this->value($contact, $version, 0, ['value_string' => '+15705559999']);
        $this->value($contact, $version, 1, ['value_string' => '+15705551212']);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringNotContainsString('+15705559999', $card);
        $this->assertSame(1, substr_count($card, '+15705551212'));
    }

    #[Test]
    public function it_keeps_a_card_without_the_photo_the_avatar_re_emits(): void
    {
        $merger = new VCardMerger;

        $kept = $merger->forKeeping("BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nFN:Dwight Schrute\r\nPHOTO:data:image/jpeg;base64,QQ==\r\nORG:Schrute Farms\r\nEND:VCARD\r\n");

        $this->assertStringNotContainsString('PHOTO', $kept);
        $this->assertStringContainsString('ORG:Schrute Farms', $kept);
    }

    /** @param  array<string, mixed>  $attributes */
    private function labelOn(StructureVersion $version, int $index, ?string $key, string $label): ValueListItem
    {
        $field = $this->fieldsOf($version)[$index];
        $list = ValueList::factory()->create(['account_id' => $field->account_id]);

        $field->value_list_id = $list->id;
        $field->save();

        return ValueListItem::factory()->create([
            'account_id' => $field->account_id,
            'value_list_id' => $list->id,
            'key' => $key,
            'label' => $label,
        ]);
    }

    #[Test]
    public function it_writes_the_categories_a_contact_holds_in_the_order_of_the_list(): void
    {
        $user = User::factory()->create();
        [$version, $list] = $this->structureWithCategories($user);
        $sales = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Sales', 'position' => 1]);
        $finerThings = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Finer Things Club', 'position' => 2]);
        $contact = $this->contact(['structure_version_id' => $version->id]);
        $this->value($contact, $version, 0, ['value_string' => null, 'value_list_item_ids' => [$finerThings->id, $sales->id]]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('CATEGORIES:Sales,Finer Things Club', $card);
    }

    #[Test]
    public function it_takes_the_categories_a_device_wrote_off_a_contact_holding_none(): void
    {
        $user = User::factory()->create();
        [$version] = $this->structureWithCategories($user);
        $contact = $this->contact([
            'structure_version_id' => $version->id,
            'vcard' => "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:jim\r\nCATEGORIES:Pranks\r\nEND:VCARD\r\n",
        ]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringNotContainsString('CATEGORIES', $card);
    }

    #[Test]
    public function it_leaves_the_categories_a_device_wrote_when_the_structure_holds_none(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [['type' => FieldTypeEnum::Phone, 'label' => 'Work']]);
        $contact = $this->contact([
            'structure_version_id' => $version->id,
            'vcard' => "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:jim\r\nCATEGORIES:Pranks\r\nEND:VCARD\r\n",
        ]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('CATEGORIES:Pranks', $card);
    }

    /** @return array{0: StructureVersion, 1: ValueList} */
    private function structureWithCategories(User $user): array
    {
        $version = Structure::withFields($user, [['type' => FieldTypeEnum::Multiselect, 'label' => 'Category']]);
        $list = ValueList::factory()->supplied(ValueListEnum::ContactCategories)->create(['account_id' => $user->account_id]);
        $this->fieldsOf($version)[0]->update(['value_list_id' => $list->id]);

        return [$version, $list];
    }

    private function contact(array $attributes = []): Contact
    {
        return Contact::factory()->create($attributes);
    }

    /** @param  array<string, mixed>  $attributes */
    private function value(Contact $contact, StructureVersion $version, int $index, array $attributes): FieldValue
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

    /** @return array<string, string> */
    private function slough(): array
    {
        return [
            'po_box' => 'PO Box 12',
            'extended' => 'Suite 3',
            'street' => '1725 Slough Avenue',
            'city' => 'Scranton',
            'region' => 'PA',
            'postal_code' => '18505',
            'country' => 'USA',
        ];
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
    #[DataProvider('clientCards')]
    public function it_hands_each_client_back_its_own_card(string $fixture): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Structure::asTheDefaultOf($user, $vault, [
            ['type' => FieldTypeEnum::Email, 'label' => 'Email'],
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
            ['type' => FieldTypeEnum::Address, 'label' => 'Home'],
            ['type' => FieldTypeEnum::RecurringDate, 'key' => 'birthday', 'label' => 'Birthday'],
        ]);

        $sent = (string) file_get_contents(base_path('tests/Fixtures/Dav/'.$fixture));

        $contact = new ImportVCard(user: $user, vault: $vault, card: $sent, davUri: $fixture)->execute();

        $readBack = $this->propertiesNobodyOwns(new VCardMerger()->merge($contact));

        foreach ($this->propertiesNobodyOwns($sent) as $property => $values) {
            $this->assertEquals($values, $readBack[$property] ?? [], $fixture.' did not hand back '.$property);
        }
    }

    /** @return array<int, array<int, string>> */
    public static function clientCards(): array
    {
        return [
            ['apple-contacts.vcf'],
            ['davx5.vcf'],
            ['thunderbird.vcf'],
            ['evolution.vcf'],
            ['kontact.vcf'],
        ];
    }

    /** @return array<string, array<int, string>> */
    private function propertiesNobodyOwns(string $card): array
    {
        $owned = ['N', 'FN', 'NICKNAME', 'PHOTO', 'BDAY', 'EMAIL', 'TEL', 'URL', 'ADR', 'X-ABDATE', 'REV', 'VERSION', 'PRODID', 'UID', 'X-ABLABEL'];

        $document = Reader::read($card, Reader::OPTION_FORGIVING)->convert(Document::VCARD40);
        $kept = [];

        foreach ($document->children() as $property) {
            $name = mb_strtoupper($property->name);

            if (in_array($name, $owned, true)) {
                continue;
            }

            $value = (string) $property->getValue();

            if (mb_trim($value) === '') {
                continue;
            }

            $kept[$name][] = $value;
        }

        return $kept;
    }

    #[Test]
    public function it_writes_every_way_the_name_is_written_as_alternatives_of_one_name(): void
    {
        $contact = $this->minji();

        $card = Reader::read(new VCardMerger()->merge($contact));
        $names = iterator_to_array($card->select('N'), false);
        $formatted = iterator_to_array($card->select('FN'), false);

        $this->assertCount(4, $names);
        $this->assertCount(3, $formatted);
        $this->assertSame(['1'], array_values(array_unique(array_map(fn ($property): string => (string) $property['ALTID'], [...$names, ...$formatted]))));
        $this->assertSame('김민지', (string) collect($formatted)->first(fn ($property): bool => (string) $property['PREF'] === '1')?->getValue());
        $this->assertSame('x-rr', (string) collect($names)->first(fn ($property): bool => isset($property['PHONETIC']))?->offsetGet('PHONETIC'));
        $this->assertSame('transliteration', (string) collect($formatted)->first(fn ($property): bool => (string) $property->getValue() === 'Kim Min-ji')?->offsetGet('X-NAME-KIND'));
        $this->assertSame('Minnie', (string) $card->NICKNAME);
    }

    #[Test]
    public function it_writes_one_alternative_identifier_for_names_that_came_from_different_places(): void
    {
        $contact = $this->minji();
        $representations = $contact->nameRepresentations()->get();
        $representations[0]->update(['vcard_alt_id' => null]);
        $representations[1]->update(['vcard_alt_id' => 'scranton']);
        $representations[2]->update(['vcard_alt_id' => 'stamford']);

        $card = Reader::read(new VCardMerger()->merge($contact));
        $altIds = array_map(fn ($property): string => (string) $property['ALTID'], [...$card->select('N'), ...$card->select('FN')]);

        $this->assertSame(['scranton'], array_values(array_unique($altIds)));
    }

    #[Test]
    public function it_brings_back_the_order_of_a_name_and_a_display_name_that_is_not_the_first(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Structure::asTheDefaultOf($user, $vault);
        $card = "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:kim-min-ji\r\n"
            ."N;ALTID=1;LANGUAGE=ko;SCRIPT=Latn;PHONETIC=x-rr:Gim;Minji;;;\r\n"
            ."N;ALTID=1;LANGUAGE=ko;SCRIPT=Latn;X-NAME-KIND=transliteration:Kim;Minji;;;\r\n"
            ."FN;ALTID=1;LANGUAGE=ko;SCRIPT=Latn;X-NAME-KIND=transliteration:Kim Minji\r\n"
            ."N;ALTID=1;LANGUAGE=ko;SCRIPT=Hang:김;민지;;;\r\n"
            ."FN;ALTID=1;LANGUAGE=ko;SCRIPT=Hang;PREF=1:김민지\r\n"
            ."END:VCARD\r\n";
        $contact = new ImportVCard(user: $user, vault: $vault, card: $card)->execute();
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        Structure::asTheDefaultOf($user, $elsewhere);

        $copy = new ImportVCard(user: $user, vault: $elsewhere, card: new VCardMerger()->merge($contact))->execute();

        $read = fn (Contact $one): array => $one->nameRepresentations()->get()->map->toComparable()->all();
        $this->assertSame('family_first', $read($contact)[1]['name_order']);
        $this->assertSame($read($contact), $read($copy));
        $this->assertSame('김민지', $copy->name);
        $this->assertSame(['Gim', 'Minji'], [$read($copy)[0]['family_name'], $read($copy)[0]['given_name']]);
    }

    #[Test]
    public function it_keeps_the_parameters_a_device_wrote_that_it_does_not_manage(): void
    {
        $contact = $this->minji();

        $exported = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('SORT-AS=Kim', str_replace("\r\n ", '', $exported));
        $this->assertStringContainsString('X-ORIGIN=family-register', str_replace("\r\n ", '', $exported));
    }

    #[Test]
    public function it_loses_nothing_of_a_name_through_an_export_and_an_import(): void
    {
        $contact = $this->minji();
        $elsewhere = Vault::factory()->create(['account_id' => $contact->account_id]);
        $user = User::query()->where('account_id', $contact->account_id)->sole();
        Structure::asTheDefaultOf($user, $elsewhere);

        $copy = new ImportVCard(user: $user, vault: $elsewhere, card: new VCardMerger()->merge($contact))->execute();

        $read = fn (Contact $one): array => $one->nameRepresentations()->get()->map->toComparable()->all();
        $this->assertSame($read($contact), $read($copy));
        $this->assertSame($contact->name, $copy->name);
    }

    #[Test]
    public function it_writes_a_single_plain_name_the_way_it_always_has(): void
    {
        $contact = Contact::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);

        $exported = new VCardMerger()->merge($contact);

        $this->assertStringContainsString("N:Schrute;Dwight;;;\r\n", $exported);
        $this->assertStringNotContainsString('ALTID', $exported);
    }

    private function minji(): Contact
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Structure::asTheDefaultOf($user, $vault);

        return new ImportVCard(user: $user, vault: $vault, card: (string) file_get_contents(base_path('tests/Fixtures/Dav/multilingual-names.vcf')))->execute();
    }
}
