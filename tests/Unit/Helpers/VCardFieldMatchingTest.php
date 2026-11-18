<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\FieldTypeEnum;
use App\Helpers\VCardFieldMatching;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\StructureVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Property;
use Tests\Fixtures\Dav\Structure;
use Tests\TestCase;

class VCardFieldMatchingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_pairs_a_line_with_the_value_it_equals_whatever_its_position(): void
    {
        [$contact, $version] = $this->contactOn([
            ['type' => FieldTypeEnum::Url, 'label' => 'Website'],
            ['type' => FieldTypeEnum::Url, 'label' => 'LinkedIn'],
        ]);
        $this->write($contact, $version, 0, 'https://schrutefarms.test');
        $this->write($contact, $version, 1, 'https://linkedin.test/dwight');

        $matched = $this->match($contact, 'URL', ['https://linkedin.test/dwight', 'https://schrutefarms.test']);

        $this->assertSame([
            ['Website', 'https://schrutefarms.test', 'https://schrutefarms.test'],
            ['LinkedIn', 'https://linkedin.test/dwight', 'https://linkedin.test/dwight'],
        ], $this->read($matched['pairs']));
    }

    #[Test]
    public function it_pairs_writings_of_one_value_that_differ_only_in_form(): void
    {
        [$contact, $version] = $this->contactOn([
            ['type' => FieldTypeEnum::Phone, 'label' => 'Office'],
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
        ]);
        $this->write($contact, $version, 0, '+15705550100');
        $this->write($contact, $version, 1, '+15705551212');

        $matched = $this->match($contact, 'TEL', ['tel:+1 570-555-1212', '+1 (570) 555-0100']);

        $this->assertSame([
            ['Office', '+15705550100', '+1 (570) 555-0100'],
            ['Mobile', '+15705551212', 'tel:+1 570-555-1212'],
        ], $this->read($matched['pairs']));
    }

    #[Test]
    public function it_pairs_what_is_left_in_order_so_a_changed_value_replaces_the_old_one(): void
    {
        [$contact, $version] = $this->contactOn([
            ['type' => FieldTypeEnum::Email, 'label' => 'Work'],
            ['type' => FieldTypeEnum::Email, 'label' => 'Personal'],
        ]);
        $this->write($contact, $version, 0, 'dwight@dundermifflin.test');
        $this->write($contact, $version, 1, 'dwight@schrutefarms.test');

        $matched = $this->match($contact, 'EMAIL', ['dwight@schrutefarms.test', 'dwight@stamford.test']);

        $this->assertSame([
            ['Personal', 'dwight@schrutefarms.test', 'dwight@schrutefarms.test'],
            ['Work', 'dwight@dundermifflin.test', 'dwight@stamford.test'],
        ], $this->read($matched['pairs']));
    }

    #[Test]
    public function it_pairs_a_value_the_card_no_longer_carries_with_no_line(): void
    {
        [$contact, $version] = $this->contactOn([
            ['type' => FieldTypeEnum::Email, 'label' => 'Work'],
        ]);
        $this->write($contact, $version, 0, 'dwight@dundermifflin.test');

        $matched = $this->match($contact, 'EMAIL', []);

        $this->assertSame([['Work', 'dwight@dundermifflin.test', null]], $this->read($matched['pairs']));
    }

    #[Test]
    public function it_leaves_a_line_nothing_claims_out_of_every_pair(): void
    {
        [$contact, $version] = $this->contactOn([
            ['type' => FieldTypeEnum::Phone, 'label' => 'Office'],
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
        ]);
        $this->write($contact, $version, 0, '+15705550100');

        $matched = $this->match($contact, 'TEL', ['+15705550100', '+15705551212']);

        $this->assertSame([['Office', '+15705550100', '+15705550100']], $this->read($matched['pairs']));
    }

    #[Test]
    public function it_fills_the_empty_fields_in_order_on_a_card_creating_a_contact(): void
    {
        [$contact, $version] = $this->contactOn([
            ['type' => FieldTypeEnum::Phone, 'label' => 'Private line', 'is_sensitive' => true],
            ['type' => FieldTypeEnum::Phone, 'label' => 'Office'],
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
        ]);
        $this->write($contact, $version, 1, '+15705550100');

        $matched = $this->match($contact, 'TEL', ['+15705550100', '+15705551212', '+15705559999'], fillsEmptyFields: true);

        $this->assertSame([
            ['Office', '+15705550100', '+15705550100'],
            ['Mobile', null, '+15705551212'],
        ], $this->read($matched['pairs']));
    }

    #[Test]
    public function it_withholds_the_line_of_a_field_the_key_cannot_reach_and_gives_that_field_nothing_else(): void
    {
        [$contact, $version] = $this->contactOn([
            ['type' => FieldTypeEnum::Phone, 'label' => 'Private line', 'is_sensitive' => true],
            ['type' => FieldTypeEnum::Phone, 'label' => 'Mobile'],
        ]);
        $this->write($contact, $version, 0, '+15705559999');
        $this->write($contact, $version, 1, '+15705551212');

        $plain = $this->match($contact, 'TEL', ['+15705559999', '+15705551212']);
        $sensitive = $this->match($contact, 'TEL', ['+15705551212'], reachesSensitiveFields: true);

        $this->assertSame([['Mobile', '+15705551212', '+15705551212']], $this->read($plain['pairs']));
        $this->assertSame(['+15705559999'], array_map(fn (Property $line): string => (string) $line->getValue(), $plain['withheld']));
        $this->assertSame([
            ['Mobile', '+15705551212', '+15705551212'],
            ['Private line', '+15705559999', null],
        ], $this->read($sensitive['pairs']));
    }

    /**
     * @param  array<int, array{type: FieldTypeEnum, label: string, is_sensitive?: bool}>  $fields
     * @return array{0: Contact, 1: StructureVersion}
     */
    private function contactOn(array $fields): array
    {
        $version = Structure::withFields(User::factory()->create(), $fields);

        return [Contact::factory()->create(['structure_version_id' => $version->id]), $version];
    }

    private function write(Contact $contact, StructureVersion $version, int $index, string $value): void
    {
        $field = $version->sectionVersions()->with('fields')->get()->flatMap->fields->values()[$index];

        FieldValue::factory()->create([
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => $value,
        ]);
    }

    /**
     * @param  array<int, string>  $lines
     * @return array{pairs: list<array{field: Field, value: ?FieldValue, line: ?Property}>, withheld: list<Property>}
     */
    private function match(Contact $contact, string $property, array $lines, bool $fillsEmptyFields = false, bool $reachesSensitiveFields = false): array
    {
        $card = new VCard(['VERSION' => '4.0']);
        $added = array_map(fn (string $line): Property => $card->add($property, $line), $lines);

        return new VCardFieldMatching(contact: $contact, reachesSensitiveFields: $reachesSensitiveFields)
            ->match(property: $property, lines: $added, values: $contact->fieldValues()->get(), fillsEmptyFields: $fillsEmptyFields);
    }

    /**
     * @param  list<array{field: Field, value: ?FieldValue, line: ?Property}>  $pairs
     * @return list<array{0: string, 1: ?string, 2: ?string}>
     */
    private function read(array $pairs): array
    {
        return array_map(fn (array $pair): array => [
            $pair['field']->label,
            $pair['value']?->value_string,
            $pair['line'] === null ? null : (string) $pair['line']->getValue(),
        ], $pairs);
    }
}
