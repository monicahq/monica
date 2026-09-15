<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SetFieldValues;
use App\Enums\FieldTypeEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Enums\RepetitionOrderEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\ReminderNeedsADate;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetFieldValuesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_saves_a_whole_section_at_once(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $city = $this->fieldOn($contact, ['key' => 'city']);
        $notes = $this->fieldOn($contact, ['key' => 'notes', 'type' => FieldTypeEnum::Textarea], beside: $city->sectionVersion);

        $written = new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($city),
            values: [$city->id => 'Scranton', $notes->id => 'Sells paper.'],
        )->execute();

        $this->assertSame(2, $written);
        $this->assertDatabaseHas('field_values', ['field_id' => $city->id, 'value_string' => 'Scranton']);
        $this->assertDatabaseHas('field_values', ['field_id' => $notes->id, 'value_text' => 'Sells paper.']);
    }

    #[Test]
    public function it_clears_a_field_somebody_emptied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $city = $this->fieldOn($contact, ['key' => 'city']);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($city),
            values: [$city->id => 'Scranton'],
        )->execute();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($city),
            values: [$city->id => ''],
        )->execute();

        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_leaves_a_field_that_was_already_empty_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $city = $this->fieldOn($contact, ['key' => 'city']);

        $written = new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($city),
            values: [$city->id => null],
        )->execute();

        $this->assertSame(0, $written);
    }

    #[Test]
    public function it_keeps_a_no_because_a_no_is_an_answer(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::Boolean]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($field),
            values: [$field->id => false],
        )->execute();

        $this->assertDatabaseHas('field_values', ['field_id' => $field->id, 'value_boolean' => false]);
    }

    #[Test]
    public function it_writes_the_item_a_choice_points_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $item = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::Select, 'value_list_id' => $list->id]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($field),
            values: [$field->id => (string) $item->id],
        )->execute();

        $this->assertDatabaseHas('field_values', ['field_id' => $field->id, 'value_list_item_id' => $item->id]);
    }

    #[Test]
    public function it_writes_every_item_a_multiple_choice_points_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $first = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        $second = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::Multiselect, 'value_list_id' => $list->id]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($field),
            values: [$field->id => [(string) $first->id, (string) $second->id]],
        )->execute();

        $value = FieldValue::query()->where('field_id', $field->id)->firstOrFail();

        $this->assertEqualsCanonicalizing([$first->id, $second->id], $value->value_list_item_ids);
    }

    #[Test]
    public function it_carries_the_unknown_year_of_a_date_that_comes_round_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::RecurringDate]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($field),
            values: [$field->id => '1964-03-15'],
            yearIsUnknown: [$field->id => true],
        )->execute();

        $value = FieldValue::query()->where('field_id', $field->id)->firstOrFail();

        $this->assertTrue($value->year_is_unknown);
        $this->assertEquals(FieldValue::PLACEHOLDER_YEAR, (int) $value->value_date->format('Y'));
    }

    #[Test]
    public function it_refuses_a_required_field_left_empty(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['is_required' => true, 'label' => 'City']);

        $this->expectException(FieldIsRequired::class);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($field),
            values: [$field->id => ''],
        )->execute();
    }

    #[Test]
    public function it_writes_nothing_at_all_when_one_required_field_is_empty(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $city = $this->fieldOn($contact, ['key' => 'city']);
        $required = $this->fieldOn($contact, ['key' => 'diet', 'is_required' => true], beside: $city->sectionVersion);

        try {
            new SetFieldValues(
                user: $user,
                record: $contact,
                section: $this->sectionOf($city),
                values: [$city->id => 'Scranton', $required->id => ''],
            )->execute();
        } catch (FieldIsRequired) {
        }

        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_leaves_a_type_it_has_no_control_for_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $relation = $this->fieldOn($contact, ['type' => FieldTypeEnum::Relation, 'is_required' => true]);

        $written = new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($relation),
            values: [$relation->id => 'anything'],
        )->execute();

        $this->assertSame(0, $written);
        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_refuses_a_record_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->expectException(ModelNotFoundException::class);

        new SetFieldValues(
            user: User::factory()->create(),
            record: $contact,
            section: $this->sectionOf($this->fieldOn($contact)),
            values: [],
        )->execute();
    }

    #[Test]
    public function it_refuses_a_section_of_another_structure(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $elsewhere = StructureVersion::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: StructureVersionSection::factory()->create([
                'account_id' => $user->account_id,
                'structure_version_id' => $elsewhere->id,
            ]),
            values: [],
        )->execute();
    }

    #[Test]
    public function it_lets_a_section_be_saved_while_another_holds_a_required_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $city = $this->fieldOn($contact, ['key' => 'city']);
        $this->fieldOn($contact, ['key' => 'diet', 'is_required' => true]);

        $written = new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($city),
            values: [$city->id => 'Scranton'],
        )->execute();

        $this->assertSame(1, $written);
        $this->assertDatabaseHas('field_values', ['field_id' => $city->id, 'value_string' => 'Scranton']);
    }

    #[Test]
    public function it_adds_the_repetitions_somebody_filled_in(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $documents = $this->fieldOn($contact, ['key' => 'documents', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $description = $this->childOf($documents, ['key' => 'description']);

        $written = new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($documents),
            values: [$documents->id => [
                ['values' => [$description->id => 'The lease']],
                ['values' => [$description->id => 'The insurance']],
            ]],
        )->execute();

        $this->assertSame(2, $written);
        $this->assertSame(2, FieldValueRow::query()->count());
        $this->assertDatabaseHas('field_values', ['field_id' => $description->id, 'value_string' => 'The lease']);
        $this->assertDatabaseHas('field_values', ['field_id' => $description->id, 'value_string' => 'The insurance']);
    }

    #[Test]
    public function it_adds_no_repetition_for_one_nobody_filled_in(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $documents = $this->fieldOn($contact, ['key' => 'documents', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $description = $this->childOf($documents, ['key' => 'description']);

        $written = new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($documents),
            values: [$documents->id => [['values' => [$description->id => '']]]],
        )->execute();

        $this->assertSame(0, $written);
        $this->assertSame(0, FieldValueRow::query()->count());
    }

    #[Test]
    public function it_rewrites_a_repetition_the_record_already_holds(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $documents = $this->fieldOn($contact, ['key' => 'documents', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $description = $this->childOf($documents, ['key' => 'description']);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($documents),
            values: [$documents->id => [['values' => [$description->id => 'The lease']]]],
        )->execute();

        $row = FieldValueRow::query()->sole();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($documents),
            values: [$documents->id => [['id' => $row->id, 'values' => [$description->id => 'The lease, signed']]]],
        )->execute();

        $this->assertSame(1, FieldValueRow::query()->count());
        $this->assertDatabaseHas('field_values', ['field_id' => $description->id, 'value_string' => 'The lease, signed']);
    }

    #[Test]
    public function it_takes_away_a_repetition_that_did_not_come_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $documents = $this->fieldOn($contact, ['key' => 'documents', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $description = $this->childOf($documents, ['key' => 'description']);
        $section = $this->sectionOf($documents);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$documents->id => [
                ['values' => [$description->id => 'The lease']],
                ['values' => [$description->id => 'The insurance']],
            ]],
        )->execute();

        $kept = FieldValueRow::query()->orderBy('position')->first();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$documents->id => [['id' => $kept->id, 'values' => [$description->id => 'The lease']]]],
        )->execute();

        $this->assertSame(1, FieldValueRow::query()->count());
        $this->assertDatabaseMissing('field_values', ['value_string' => 'The insurance']);
    }

    #[Test]
    public function it_writes_the_file_a_repetition_carries(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $documents = $this->fieldOn($contact, ['key' => 'documents', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $file = $this->childOf($documents, ['key' => 'file', 'type' => FieldTypeEnum::File]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($documents),
            values: [$documents->id => [
                ['values' => [$file->id => UploadedFile::fake()->create('lease.pdf', 4, 'application/pdf')]],
            ]],
        )->execute();

        $stored = File::query()->sole();

        $this->assertSame('lease.pdf', $stored->original_name);
        $this->assertSame(1, $stored->reference_count);
        $this->assertDatabaseHas('field_values', ['field_id' => $file->id, 'value_file_id' => $stored->id]);
    }

    #[Test]
    public function it_leaves_a_file_alone_when_nothing_was_uploaded_over_it(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $documents = $this->fieldOn($contact, ['key' => 'documents', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $file = $this->childOf($documents, ['key' => 'file', 'type' => FieldTypeEnum::File]);
        $description = $this->childOf($documents, ['key' => 'description']);
        $section = $this->sectionOf($documents);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$documents->id => [
                ['values' => [
                    $file->id => UploadedFile::fake()->create('lease.pdf', 4, 'application/pdf'),
                    $description->id => 'The lease',
                ]],
            ]],
        )->execute();

        $row = FieldValueRow::query()->sole();
        $stored = File::query()->sole();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$documents->id => [
                ['id' => $row->id, 'values' => [$description->id => 'The lease, signed']],
            ]],
        )->execute();

        $this->assertDatabaseHas('field_values', ['field_id' => $file->id, 'value_file_id' => $stored->id]);
        $this->assertDatabaseHas('field_values', ['field_id' => $description->id, 'value_string' => 'The lease, signed']);
        $this->assertSame(1, $stored->refresh()->reference_count);
    }

    #[Test]
    public function it_refuses_a_repeater_left_empty_when_the_template_asks_for_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $documents = $this->fieldOn($contact, [
            'key' => 'documents',
            'type' => FieldTypeEnum::Repeater,
            'is_repeatable' => true,
            'is_required' => true,
            'label' => 'Documents',
        ]);
        $description = $this->childOf($documents, ['key' => 'description']);

        $this->expectException(FieldIsRequired::class);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($documents),
            values: [$documents->id => [['values' => [$description->id => '']]]],
        )->execute();
    }

    #[Test]
    public function it_lets_two_repeaters_in_one_section_collect_at_different_ends(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $notes = $this->fieldOn($contact, [
            'key' => 'notes',
            'type' => FieldTypeEnum::Repeater,
            'is_repeatable' => true,
            'repetition_order' => RepetitionOrderEnum::NewestFirst,
        ]);
        $note = $this->childOf($notes, ['key' => 'note']);

        $documents = $this->fieldOn($contact, [
            'key' => 'documents',
            'type' => FieldTypeEnum::Repeater,
            'is_repeatable' => true,
        ], beside: $notes->sectionVersion);
        $description = $this->childOf($documents, ['key' => 'description']);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($notes),
            values: [
                $notes->id => [['values' => [$note->id => 'Where we met']]],
                $documents->id => [['values' => [$description->id => 'The lease']]],
            ],
        )->execute();

        $keptNote = FieldValueRow::query()->where('field_id', $notes->id)->sole();
        $keptDocument = FieldValueRow::query()->where('field_id', $documents->id)->sole();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($notes),
            values: [
                $notes->id => [
                    0 => ['values' => [$note->id => 'What they said on Tuesday']],
                    1 => ['id' => $keptNote->id, 'values' => [$note->id => 'Where we met']],
                ],
                $documents->id => [
                    0 => ['id' => $keptDocument->id, 'values' => [$description->id => 'The lease']],
                    1 => ['values' => [$description->id => 'The insurance']],
                ],
            ],
        )->execute();

        $this->assertSame(
            ['What they said on Tuesday', 'Where we met'],
            $this->descriptionsIn($contact, $notes, $note),
        );
        $this->assertSame(
            ['The lease', 'The insurance'],
            $this->descriptionsIn($contact, $documents, $description),
        );
    }

    #[Test]
    public function it_keeps_two_repetitions_added_in_one_save_in_the_order_they_were_typed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $notes = $this->fieldOn($contact, [
            'key' => 'notes',
            'type' => FieldTypeEnum::Repeater,
            'is_repeatable' => true,
            'repetition_order' => RepetitionOrderEnum::NewestFirst,
        ]);
        $note = $this->childOf($notes, ['key' => 'note']);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($notes),
            values: [$notes->id => [['values' => [$note->id => 'Where we met']]]],
        )->execute();

        $held = FieldValueRow::query()->sole();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($notes),
            values: [$notes->id => [
                0 => ['values' => [$note->id => 'Typed in the upper box']],
                1 => ['values' => [$note->id => 'Typed in the lower box']],
                2 => ['id' => $held->id, 'values' => [$note->id => 'Where we met']],
            ]],
        )->execute();

        $this->assertSame(
            ['Typed in the upper box', 'Typed in the lower box', 'Where we met'],
            $this->descriptionsIn($contact, $notes, $note),
        );
    }

    #[Test]
    public function it_loses_nothing_when_a_repeater_that_collects_at_the_front_is_saved(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $notes = $this->fieldOn($contact, [
            'key' => 'notes',
            'type' => FieldTypeEnum::Repeater,
            'is_repeatable' => true,
            'repetition_order' => RepetitionOrderEnum::NewestFirst,
        ]);
        $note = $this->childOf($notes, ['key' => 'note']);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($notes),
            values: [$notes->id => [
                ['values' => [$note->id => 'Where we met']],
                ['values' => [$note->id => 'The thing about the move']],
            ]],
        )->execute();

        $held = FieldValueRow::query()->orderBy('position')->orderBy('id')->get();
        $this->assertCount(2, $held);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($notes),
            values: [$notes->id => [
                0 => ['values' => [$note->id => 'What they said on Tuesday']],
                1 => ['id' => $held[0]->id, 'values' => [$note->id => 'Where we met']],
            ]],
        )->execute();

        $this->assertSame(
            ['What they said on Tuesday', 'Where we met'],
            $this->descriptionsIn($contact, $notes, $note),
            'The new note is first, the kept one survived, and the removed one is gone.',
        );
        $this->assertSame([1, 2], FieldValueRow::query()->orderBy('position')->pluck('position')->all());
    }

    #[Test]
    public function it_numbers_the_repetitions_in_the_order_they_arrived(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $documents = $this->fieldOn($contact, ['key' => 'documents', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $description = $this->childOf($documents, ['key' => 'description']);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($documents),
            values: [$documents->id => [
                ['values' => [$description->id => 'First']],
                ['values' => [$description->id => 'Second']],
                ['values' => [$description->id => 'Third']],
            ]],
        )->execute();

        $this->assertSame([1, 2, 3], FieldValueRow::query()->orderBy('id')->pluck('position')->all());
        $this->assertSame(['First', 'Second', 'Third'], $this->descriptionsIn($contact, $documents, $description));
    }

    #[Test]
    public function it_writes_no_place_when_a_save_moves_nothing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $documents = $this->fieldOn($contact, ['key' => 'documents', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $description = $this->childOf($documents, ['key' => 'description']);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($documents),
            values: [$documents->id => [
                ['values' => [$description->id => 'The lease']],
                ['values' => [$description->id => 'The insurance']],
            ]],
        )->execute();

        $rows = FieldValueRow::query()->orderBy('id')->get();
        $touched = $rows->pluck('updated_at')->all();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($documents),
            values: [$documents->id => [
                ['id' => $rows[0]->id, 'values' => [$description->id => 'The lease, corrected']],
                ['id' => $rows[1]->id, 'values' => [$description->id => 'The insurance']],
            ]],
        )->execute();

        $this->assertSame([1, 2], FieldValueRow::query()->orderBy('id')->pluck('position')->all());
        $this->assertEquals($touched, FieldValueRow::query()->orderBy('id')->get()->pluck('updated_at')->all());
    }

    #[Test]
    public function it_closes_the_gap_a_removal_leaves(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $documents = $this->fieldOn($contact, ['key' => 'documents', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $description = $this->childOf($documents, ['key' => 'description']);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($documents),
            values: [$documents->id => [
                ['values' => [$description->id => 'First']],
                ['values' => [$description->id => 'Second']],
                ['values' => [$description->id => 'Third']],
            ]],
        )->execute();

        $rows = FieldValueRow::query()->orderBy('position')->orderBy('id')->get();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($documents),
            values: [$documents->id => [
                ['id' => $rows[0]->id, 'values' => [$description->id => 'First']],
                ['id' => $rows[2]->id, 'values' => [$description->id => 'Third']],
            ]],
        )->execute();

        $this->assertSame([1, 2], FieldValueRow::query()->orderBy('position')->pluck('position')->all());
        $this->assertSame(['First', 'Third'], $this->descriptionsIn($contact, $documents, $description));
    }

    #[Test]
    public function it_reads_the_order_off_the_index_rather_than_off_the_body(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $documents = $this->fieldOn($contact, ['key' => 'documents', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $description = $this->childOf($documents, ['key' => 'description']);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($documents),
            values: [$documents->id => [
                2 => ['values' => [$description->id => 'Third']],
                0 => ['values' => [$description->id => 'First']],
                1 => ['values' => [$description->id => 'Second']],
            ]],
        )->execute();

        $this->assertSame(['First', 'Second', 'Third'], $this->descriptionsIn($contact, $documents, $description));
    }

    #[Test]
    public function it_writes_the_reminder_asked_for_under_a_date(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::RecurringDate, 'label' => 'Birthday']);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($field),
            values: [$field->id => '1984-02-03'],
            reminders: [$field->id => $this->asked()],
        )->execute();

        $value = FieldValue::query()->firstWhere('field_id', $field->id);

        $this->assertDatabaseHas('reminders', [
            'field_value_id' => $value->id,
            'field_id' => $field->id,
            'remindable_id' => $contact->id,
            'name' => 'Ring Pam',
            'cadence' => ReminderCadenceEnum::Yearly->value,
            'is_active' => true,
        ]);
        $this->assertEquals(
            [['direction' => 'before', 'amount' => 7, 'unit' => 'days'], ['direction' => 'on', 'amount' => null, 'unit' => null]],
            Reminder::query()->firstWhere('field_value_id', $value->id)->moments,
        );
    }

    #[Test]
    public function it_writes_no_reminder_when_the_box_is_left_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::Date]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($field),
            values: [$field->id => '1984-02-03'],
            reminders: [$field->id => $this->asked(['on' => null])],
        )->execute();

        $this->assertEquals(0, Reminder::query()->count());
    }

    #[Test]
    public function it_refuses_a_reminder_asked_for_over_a_date_nobody_gave(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::RecurringDate]);

        try {
            new SetFieldValues(
                user: $user,
                record: $contact,
                section: $this->sectionOf($field),
                values: [$field->id => ''],
                reminders: [$field->id => $this->asked()],
            )->execute();

            $this->fail('The save should have been refused.');
        } catch (ReminderNeedsADate) {
            $this->assertEquals(0, Reminder::query()->count());
            $this->assertEquals(0, FieldValue::query()->count());
        }
    }

    #[Test]
    public function it_changes_the_reminder_a_date_already_carries(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::RecurringDate]);
        $section = $this->sectionOf($field);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$field->id => '1984-02-03'],
            reminders: [$field->id => $this->asked()],
        )->execute();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$field->id => '1984-02-03'],
            reminders: [$field->id => $this->asked(['name' => 'Send a card', 'recurs' => '0'])],
        )->execute();

        $this->assertEquals(1, Reminder::query()->count());
        $this->assertDatabaseHas('reminders', [
            'name' => 'Send a card',
            'cadence' => ReminderCadenceEnum::Once->value,
        ]);
    }

    #[Test]
    public function it_takes_the_reminder_away_when_the_box_comes_back_unticked(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::RecurringDate]);
        $section = $this->sectionOf($field);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$field->id => '1984-02-03'],
            reminders: [$field->id => $this->asked()],
        )->execute();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$field->id => '1984-02-03'],
            reminders: [$field->id => $this->asked(['on' => null])],
        )->execute();

        $this->assertEquals(0, Reminder::query()->count());
        $this->assertEquals(1, Reminder::query()->withTrashed()->count());
        $this->assertDatabaseHas('field_values', ['field_id' => $field->id, 'value_date' => '1984-02-03 00:00:00']);
    }

    #[Test]
    public function it_leaves_the_reminder_quiet_and_saying_why_when_the_date_is_cleared(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::RecurringDate]);
        $section = $this->sectionOf($field);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$field->id => '1984-02-03'],
            reminders: [$field->id => $this->asked()],
        )->execute();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$field->id => ''],
            reminders: [$field->id => $this->asked(['on' => null])],
        )->execute();

        $this->assertDatabaseHas('reminders', [
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::ValueCleared->value,
            'field_value_id' => null,
        ]);
    }

    #[Test]
    public function it_gives_a_date_typed_in_again_a_reminder_of_its_own(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::RecurringDate]);
        $section = $this->sectionOf($field);

        new SetFieldValues(user: $user, record: $contact, section: $section, values: [$field->id => '1984-02-03'], reminders: [$field->id => $this->asked()])->execute();
        new SetFieldValues(user: $user, record: $contact, section: $section, values: [$field->id => ''], reminders: [$field->id => $this->asked(['on' => null])])->execute();
        new SetFieldValues(user: $user, record: $contact, section: $section, values: [$field->id => '1990-05-05'], reminders: [$field->id => $this->asked()])->execute();

        $live = Reminder::query()->active()->get();

        $this->assertCount(1, $live);
        $this->assertEquals(FieldValue::query()->firstWhere('field_id', $field->id)->id, $live->first()->field_value_id);
        $this->assertEquals(1, Reminder::query()->onlyTrashed()->count());
    }

    #[Test]
    public function it_offers_no_reminder_on_a_field_that_holds_no_date(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::Text]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($field),
            values: [$field->id => 'Scranton'],
            reminders: [$field->id => $this->asked()],
        )->execute();

        $this->assertEquals(0, Reminder::query()->count());
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function asked(array $changes = []): array
    {
        return array_merge([
            'on' => '1',
            'name' => 'Ring Pam',
            'recurs' => '1',
            'cadence' => 'yearly',
            'interval' => '1',
            'time' => '09:00',
            'timezone' => 'Europe/Paris',
            'moments' => [
                0 => ['direction' => 'before', 'amount' => '7', 'unit' => 'days'],
                2 => ['direction' => 'on', 'amount' => '1', 'unit' => 'days'],
            ],
        ], $changes);
    }

    private function contactOf(User $user): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => StructureVersion::factory()->published()->create([
                'account_id' => $user->account_id,
            ]),
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
        ]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function childOf(Field $repeater, array $attributes = []): Field
    {
        $sectionVersion = $repeater->sectionVersion()->first();
        $sectionVersion->status = VersionStatusEnum::Draft;
        $sectionVersion->save();

        $child = Field::factory()->create(array_merge([
            'account_id' => $repeater->account_id,
            'section_version_id' => $repeater->section_version_id,
            'parent_field_id' => $repeater->id,
        ], $attributes));

        $sectionVersion->status = VersionStatusEnum::Published;
        $sectionVersion->save();

        $repeater->unsetRelation('children');

        return $child;
    }

    private function sectionOf(Field $field): StructureVersionSection
    {
        return StructureVersionSection::query()
            ->where('section_version_id', $field->section_version_id)
            ->firstOrFail();
    }

    /** @param  array<string, mixed>  $attributes */
    private function fieldOn(Contact $contact, array $attributes = [], ?SectionVersion $beside = null): Field
    {
        $version = $contact->structureVersion;
        $version->status = VersionStatusEnum::Draft;
        $version->save();

        $sectionVersion = $beside === null
            ? SectionVersion::factory()->create(['account_id' => $version->account_id])
            : $beside->refresh();

        $sectionVersion->status = VersionStatusEnum::Draft;
        $sectionVersion->save();

        $field = Field::factory()->create(array_merge([
            'account_id' => $version->account_id,
            'section_version_id' => $sectionVersion->id,
        ], $attributes));

        if ($beside === null) {
            StructureVersionSection::factory()->create([
                'account_id' => $version->account_id,
                'structure_version_id' => $version->id,
                'structure_version_page_id' => $version->pages->first()->id,
                'section_version_id' => $sectionVersion->id,
            ]);
        }

        $sectionVersion->status = VersionStatusEnum::Published;
        $sectionVersion->save();

        $version->status = VersionStatusEnum::Published;
        $version->save();

        $contact->unsetRelation('structureVersion');

        return $field;
    }

    /** @return list<string|null> */
    #[Test]
    public function it_replaces_the_deadlines_ahead_when_somebody_corrects_the_date(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-01-01 06:00:00');

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::RecurringDate, 'label' => 'Birthday']);
        $section = $this->sectionOf($field);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$field->id => '1984-02-03'],
            reminders: [$field->id => $this->asked()],
        )->execute();

        $before = ReminderOccurrence::query()->pluck('id');
        $this->assertTrue($before->isNotEmpty());

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$field->id => '1984-02-10'],
            reminders: [$field->id => $this->asked()],
        )->execute();

        foreach ($before as $id) {
            $this->assertDatabaseHas('reminder_occurrences', [
                'id' => $id,
                'status' => ReminderOccurrenceStatusEnum::Cancelled->value,
                'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::SourceChanged->value,
            ]);
        }

        $this->assertDatabaseHas('reminder_occurrences', [
            'source_local_date' => '2027-02-10',
            'status' => ReminderOccurrenceStatusEnum::Pending->value,
        ]);
    }

    #[Test]
    public function it_leaves_the_deadlines_alone_when_the_date_is_saved_unchanged(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-01-01 06:00:00');

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::RecurringDate, 'label' => 'Birthday']);
        $section = $this->sectionOf($field);

        foreach ([1, 2] as $ignored) {
            new SetFieldValues(
                user: $user,
                record: $contact,
                section: $section,
                values: [$field->id => '1984-02-03'],
                reminders: [$field->id => $this->asked()],
            )->execute();
        }

        $this->assertSame(0, ReminderOccurrence::query()
            ->where('status', ReminderOccurrenceStatusEnum::Cancelled->value)
            ->count());
    }

    #[Test]
    public function it_cancels_the_deadlines_when_somebody_clears_the_date(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-01-01 06:00:00');

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::RecurringDate, 'label' => 'Birthday']);
        $section = $this->sectionOf($field);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$field->id => '1984-02-03'],
            reminders: [$field->id => $this->asked()],
        )->execute();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$field->id => null],
            reminders: [$field->id => $this->asked(['on' => null])],
        )->execute();

        $this->assertSame(0, ReminderOccurrence::query()
            ->where('status', '!=', ReminderOccurrenceStatusEnum::Cancelled->value)
            ->count());
        $this->assertSame(0, ReminderOccurrence::query()
            ->where('cancelled_reason', '!=', ReminderOccurrenceCancelledReasonEnum::ValueCleared->value)
            ->count());
    }

    #[Test]
    public function it_cancels_the_deadlines_when_the_box_is_unticked(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-01-01 06:00:00');

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $contact = $this->contactOf($user);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::RecurringDate, 'label' => 'Birthday']);
        $section = $this->sectionOf($field);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$field->id => '1984-02-03'],
            reminders: [$field->id => $this->asked()],
        )->execute();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$field->id => '1984-02-03'],
            reminders: [$field->id => $this->asked(['on' => null])],
        )->execute();

        $this->assertSame(0, ReminderOccurrence::query()
            ->where('cancelled_reason', '!=', ReminderOccurrenceCancelledReasonEnum::RuleDisabled->value)
            ->count());
    }

    private function descriptionsIn(Contact $contact, Field $repeater, Field $child): array
    {
        return FieldValueRow::query()
            ->where('valuable_type', $contact->getMorphClass())
            ->where('valuable_id', $contact->id)
            ->where('field_id', $repeater->id)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (FieldValueRow $row): ?string => FieldValue::query()
                ->where('field_value_row_id', $row->id)
                ->where('field_id', $child->id)
                ->value('value_string'))
            ->all();
    }
}
