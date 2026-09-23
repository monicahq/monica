<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Reminder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldValueTest extends TestCase
{
    use RefreshDatabase;

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

    #[Test]
    public function it_holds_at_most_one_reminder_rule(): void
    {
        $reminder = Reminder::factory()->create();

        $this->assertTrue($reminder->fieldValue->reminder()->exists());
        $this->assertEquals($reminder->id, $reminder->fieldValue->reminder->id);
    }

    #[Test]
    public function it_moves_the_record_it_belongs_to_when_it_is_written(): void
    {
        $contact = Contact::factory()->create();
        $contact->forceFill(['updated_at' => CarbonImmutable::now()->subYear()])->saveQuietly();

        FieldValue::factory()->create([
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
        ]);

        $this->assertTrue($contact->refresh()->updated_at->isToday());
    }

    #[Test]
    public function it_moves_the_record_it_belongs_to_when_it_changes(): void
    {
        $contact = Contact::factory()->create();
        $value = FieldValue::factory()->create([
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
        ]);
        $contact->forceFill(['updated_at' => CarbonImmutable::now()->subYear()])->saveQuietly();

        $value->update(['value_string' => 'Utica']);

        $this->assertTrue($contact->refresh()->updated_at->isToday());
    }

    #[Test]
    public function it_moves_the_record_it_belongs_to_when_it_is_removed(): void
    {
        $contact = Contact::factory()->create();
        $value = FieldValue::factory()->create([
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
        ]);
        $contact->forceFill(['updated_at' => CarbonImmutable::now()->subYear()])->saveQuietly();

        $value->delete();

        $this->assertTrue($contact->refresh()->updated_at->isToday());
    }
}
