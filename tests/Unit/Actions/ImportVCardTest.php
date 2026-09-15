<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ImportVCard;
use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Sabre\DAV\Exception\BadRequest;
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
    public function it_takes_a_name_from_the_full_name_when_the_card_carries_no_parts(): void
    {
        Structure::asTheDefaultOf($this->user, $this->vault);

        $contact = new ImportVCard(
            user: $this->user,
            vault: $this->vault,
            card: "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:mose\r\nFN:Mose Schrute\r\nEND:VCARD\r\n",
        )->execute();

        $this->assertEquals('Mose Schrute', $contact->first_name);
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
}
