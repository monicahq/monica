<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldValueRowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $account = Account::factory()->create();
        $row = FieldValueRow::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($row->account()->exists());
        $this->assertTrue($account->is($row->account));
    }

    #[Test]
    public function it_belongs_to_the_contact_the_repetition_sits_on(): void
    {
        $contact = Contact::factory()->create(['last_name' => 'Beesly']);
        $row = FieldValueRow::factory()->create([
            'account_id' => $contact->account_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
        ]);

        $this->assertTrue($contact->is($row->valuable));
    }

    #[Test]
    public function it_belongs_to_the_entity_the_repetition_sits_on(): void
    {
        $entity = Entity::factory()->create(['name' => 'Dunder Mifflin']);
        $row = FieldValueRow::factory()->create([
            'account_id' => $entity->account_id,
            'valuable_type' => Entity::class,
            'valuable_id' => $entity->id,
        ]);

        $this->assertTrue($entity->is($row->valuable));
    }

    #[Test]
    public function it_belongs_to_the_repeater_it_is_a_repetition_of(): void
    {
        $field = Field::factory()->repeater()->create();
        $row = FieldValueRow::factory()->create(['field_id' => $field->id]);

        $this->assertTrue($row->field()->exists());
        $this->assertTrue($field->is($row->field));
        $this->assertTrue($row->field->is_repeatable);
    }

    #[Test]
    public function it_has_the_values_filled_in_on_it(): void
    {
        $row = FieldValueRow::factory()->create();
        FieldValue::factory()->create([
            'account_id' => $row->account_id,
            'valuable_type' => $row->valuable_type,
            'valuable_id' => $row->valuable_id,
            'field_value_row_id' => $row->id,
        ]);

        $this->assertTrue($row->values()->exists());
        $this->assertCount(1, $row->values);
    }

    #[Test]
    public function it_casts_the_position(): void
    {
        $row = FieldValueRow::factory()->create(['position' => 3]);

        $this->assertSame(3, $row->fresh()->position);
    }

    /**
     * Reading sorts by the place a repetition holds. Two that hold the same
     * place are separated by identity, so the same record reads the same way
     * twice rather than in whatever order a driver happened to return.
     */
    #[Test]
    public function it_reads_the_same_way_twice_when_two_repetitions_hold_one_place(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $field = Field::factory()->create(['is_repeatable' => true]);

        $first = FieldValueRow::factory()->create([
            'account_id' => $contact->vault->account_id,
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'position' => 1,
        ]);

        $second = FieldValueRow::factory()->create([
            'account_id' => $contact->vault->account_id,
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'position' => 1,
        ]);

        $read = fn (): array => FieldValueRow::query()
            ->where('valuable_type', $contact->getMorphClass())
            ->where('valuable_id', $contact->id)
            ->where('field_id', $field->id)
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([$first->id, $second->id], $read());
        $this->assertSame($read(), $read());
    }
}
