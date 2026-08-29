<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\FieldTypeEnum;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\ValueListItem;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldValueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $account = Account::factory()->create();
        $value = FieldValue::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($value->account()->exists());
        $this->assertTrue($account->is($value->account));
    }

    #[Test]
    public function it_belongs_to_a_vault(): void
    {
        $vault = Vault::factory()->create();
        $value = FieldValue::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
        ]);

        $this->assertTrue($value->vault()->exists());
        $this->assertTrue($vault->is($value->vault));
    }

    #[Test]
    public function it_belongs_to_the_record_it_was_written_on(): void
    {
        $entity = Entity::factory()->create(['name' => 'Dunder Mifflin']);
        $value = FieldValue::factory()->create([
            'account_id' => $entity->account_id,
            'vault_id' => $entity->vault_id,
            'valuable_type' => Entity::class,
            'valuable_id' => $entity->id,
        ]);

        $this->assertTrue($entity->is($value->valuable));
    }

    #[Test]
    public function it_belongs_to_the_field_it_was_entered_for(): void
    {
        $field = Field::factory()->create(['key' => 'city']);
        $value = FieldValue::factory()->create(['field_id' => $field->id]);

        $this->assertTrue($value->field()->exists());
        $this->assertTrue($field->is($value->field));
    }

    #[Test]
    public function it_belongs_to_the_repetition_it_sits_in(): void
    {
        $row = FieldValueRow::factory()->create();
        $value = FieldValue::factory()->create([
            'account_id' => $row->account_id,
            'valuable_type' => $row->valuable_type,
            'valuable_id' => $row->valuable_id,
            'field_value_row_id' => $row->id,
        ]);

        $this->assertTrue($value->row()->exists());
        $this->assertTrue($row->is($value->row));
    }

    #[Test]
    public function it_sits_in_no_repetition_when_the_field_is_outside_a_repeater(): void
    {
        $value = FieldValue::factory()->create();

        $this->assertNull($value->field_value_row_id);
        $this->assertNull($value->row);
    }

    #[Test]
    public function it_belongs_to_the_item_a_single_choice_field_points_at(): void
    {
        $item = ValueListItem::factory()->create(['label' => 'Mobile']);
        $value = FieldValue::factory()->choice($item)->create();

        $this->assertTrue($value->valueListItem()->exists());
        $this->assertTrue($item->is($value->valueListItem));
    }

    #[Test]
    public function it_belongs_to_the_file_it_holds(): void
    {
        $contact = Contact::factory()->create();
        $file = File::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
        ]);
        $value = FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'value_string' => null,
            'value_file_id' => $file->id,
        ]);

        $this->assertTrue($value->file()->exists());
        $this->assertTrue($file->is($value->file));
    }

    #[Test]
    public function it_belongs_to_the_contact_a_relation_points_at(): void
    {
        $contact = Contact::factory()->create(['last_name' => 'Schrute']);
        $value = FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'value_string' => null,
            'related_contact_id' => $contact->id,
        ]);

        $this->assertTrue($value->relatedContact()->exists());
        $this->assertTrue($contact->is($value->relatedContact));
    }

    #[Test]
    public function it_belongs_to_the_entity_a_relation_points_at(): void
    {
        $entity = Entity::factory()->create(['name' => 'Dunder Mifflin']);
        $value = FieldValue::factory()->create([
            'account_id' => $entity->account_id,
            'vault_id' => $entity->vault_id,
            'value_string' => null,
            'related_entity_id' => $entity->id,
        ]);

        $this->assertTrue($value->relatedEntity()->exists());
        $this->assertTrue($entity->is($value->relatedEntity));
    }

    #[Test]
    public function it_casts_the_typed_columns_and_the_flags(): void
    {
        $value = FieldValue::factory()->create([
            'value_string' => null,
            'value_boolean' => true,
            'value_date' => '1964-03-15',
            'value_datetime' => '2005-03-24 21:00:00',
            'value_json' => ['street' => '1725 Slough Avenue', 'city' => 'Scranton'],
            'value_list_item_ids' => [4, 8],
            'is_detached' => true,
            'detached_at' => now(),
        ]);

        $value->refresh();

        $this->assertTrue($value->value_boolean);
        $this->assertInstanceOf(CarbonImmutable::class, $value->value_date);
        $this->assertInstanceOf(CarbonImmutable::class, $value->value_datetime);
        $this->assertEquals(['street' => '1725 Slough Avenue', 'city' => 'Scranton'], $value->value_json);
        $this->assertEquals([4, 8], $value->value_list_item_ids);
        $this->assertTrue($value->is_detached);
        $this->assertInstanceOf(CarbonImmutable::class, $value->detached_at);
    }

    #[Test]
    public function it_reads_the_value_out_of_the_column_the_type_of_the_field_points_at(): void
    {
        $value = FieldValue::factory()->create(['value_string' => 'Scranton']);

        $this->assertEquals('Scranton', $value->value);
    }

    #[Test]
    public function it_reads_the_value_of_a_relation_out_of_whichever_target_it_points_at(): void
    {
        $contact = Contact::factory()->create(['last_name' => 'Schrute']);
        $value = FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => Field::factory()->relation(),
            'value_string' => null,
            'related_contact_id' => $contact->id,
        ]);

        $this->assertEquals($contact->id, $value->value);
    }

    #[Test]
    public function it_reads_no_value_at_all_for_a_field_that_stores_none(): void
    {
        $value = FieldValue::factory()->create([
            'field_id' => Field::factory()->layout(),
            'value_string' => null,
        ]);

        $this->assertEquals(FieldTypeEnum::Divider, $value->field->type);
        $this->assertNull($value->value);
    }

    #[Test]
    public function it_shows_only_the_values_that_did_not_leave_the_structure(): void
    {
        $attached = FieldValue::factory()->create(['value_string' => 'Scranton']);
        FieldValue::factory()->detached()->create(['value_string' => 'Utica']);

        $values = FieldValue::query()->attached()->get();

        $this->assertCount(1, $values);
        $this->assertTrue($attached->is($values->first()));
    }
}
