<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Dav;

use App\Actions\ImportVCard;
use App\Enums\FieldTypeEnum;
use App\Http\Dav\VCardMerger;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\TemplateVersion;
use App\Models\User;
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
    public function it_writes_a_value_the_record_holds_with_the_label_of_its_field(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Work'],
        ]);
        $contact = $this->contact(['template_version_id' => $version->id]);
        $this->value($contact, $version, 0, ['value_string' => '+15705551212']);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('TEL:+15705551212', $card);
        $this->assertStringContainsString('X-ABLabel:Work', $card);
    }

    #[Test]
    public function it_keeps_what_the_structure_has_no_field_for(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Work'],
        ]);
        $contact = $this->contact([
            'template_version_id' => $version->id,
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
            'template_version_id' => $version->id,
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
    public function it_takes_a_property_off_the_card_when_its_field_is_empty(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Email, 'label' => 'Email'],
        ]);
        $contact = $this->contact([
            'template_version_id' => $version->id,
            'vcard' => "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nFN:Dwight Schrute\r\nEMAIL:dwight@dundermifflin.test\r\nEND:VCARD\r\n",
        ]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringNotContainsString('dwight@dundermifflin.test', $card);
    }

    #[Test]
    public function it_writes_a_birthday_without_a_year_when_the_year_is_unknown(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::RecurringDate, 'key' => 'birthday', 'label' => 'Birthday'],
        ]);
        $contact = $this->contact(['template_version_id' => $version->id]);
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
        $contact = $this->contact(['template_version_id' => $version->id]);
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
        $contact = $this->contact(['template_version_id' => $version->id]);
        $this->value($contact, $version, 0, [
            'value_string' => null,
            'value_date' => '2005-03-24',
        ]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('X-ABDATE:2005-03-24', $card);
        $this->assertStringContainsString('X-ABLabel:The day we met', $card);
    }

    #[Test]
    public function it_writes_a_postal_address_in_the_seven_parts_a_card_holds(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Address, 'label' => 'Home'],
        ]);
        $contact = $this->contact(['template_version_id' => $version->id]);
        $this->value($contact, $version, 0, [
            'value_string' => null,
            'value_json' => [
                'street' => '1725 Slough Avenue',
                'city' => 'Scranton',
                'region' => 'PA',
                'postal_code' => '18505',
                'country' => 'USA',
            ],
        ]);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('ADR:;;1725 Slough Avenue;Scranton;PA;18505;USA', $card);
    }

    #[Test]
    public function it_leaves_a_sensitive_field_out_of_the_card_of_a_key_that_cannot_reach_it(): void
    {
        $user = User::factory()->create();
        $version = Structure::withFields($user, [
            ['type' => FieldTypeEnum::Phone, 'label' => 'Private line', 'is_sensitive' => true],
        ]);
        $contact = $this->contact(['template_version_id' => $version->id]);
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
            'template_version_id' => $version->id,
            'vcard' => "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight\r\nFN:Dwight Schrute\r\nTEL:+15705550000\r\nEND:VCARD\r\n",
        ]);
        $this->value($contact, $version, 0, ['value_string' => '+15705559999']);

        $card = new VCardMerger()->merge($contact);

        $this->assertStringContainsString('+15705550000', $card);
        $this->assertStringNotContainsString('+15705559999', $card);
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
    private function contact(array $attributes = []): Contact
    {
        return Contact::factory()->create($attributes);
    }

    /** @param  array<string, mixed>  $attributes */
    private function value(Contact $contact, TemplateVersion $version, int $index, array $attributes): FieldValue
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

    /** @return array<int, Field> */
    private function fieldsOf(TemplateVersion $version): array
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
}
