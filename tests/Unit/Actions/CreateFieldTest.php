<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateField;
use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\RepetitionOrderEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\AcceptedFileKindIsNotValid;
use App\Exceptions\CannotNestARepeater;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\User;
use App\Models\ValueList;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateFieldTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_mark_a_field_as_identifying(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Email,
            label: 'Email',
            isIdentifying: true,
        )->execute();

        $this->assertDatabaseHas('fields', ['id' => $field->id, 'is_identifying' => true]);
    }

    #[Test]
    public function it_marks_no_field_as_identifying_unless_it_is_told_to(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Text,
            label: 'Shoe size',
        )->execute();

        $this->assertDatabaseHas('fields', ['id' => $field->id, 'is_identifying' => false]);
    }

    #[Test]
    public function it_adds_a_field_to_a_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Text,
            label: 'Shoe size',
            helpText: 'European sizing',
            placeholder: '42',
            isRequired: true,
        )->execute();

        $this->assertInstanceOf(Field::class, $field);

        $this->assertDatabaseHas('fields', [
            'id' => $field->id,
            'account_id' => $user->account_id,
            'section_version_id' => $version->id,
            'type' => FieldTypeEnum::Text->value,
            'key' => 'shoe_size',
            'label' => 'Shoe size',
            'help_text' => 'European sizing',
            'placeholder' => '42',
            'position' => 1,
            'is_required' => true,
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::FieldCreation
                && $job->parameters === ['label' => 'Shoe size', 'name' => 'Address'];
        });
    }

    #[Test]
    public function it_takes_the_key_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Text,
            label: 'Shoe size',
            key: 'size',
        )->execute();

        $this->assertEquals('size', $field->key);
    }

    #[Test]
    public function it_counts_up_when_the_key_it_wants_is_taken(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $first = new CreateField(user: $user, sectionVersion: $version, type: FieldTypeEnum::Text, label: 'Street')->execute();
        $second = new CreateField(user: $user, sectionVersion: $version, type: FieldTypeEnum::Text, label: 'Street')->execute();

        $this->assertEquals('street', $first->key);
        $this->assertEquals('street_2', $second->key);
    }

    #[Test]
    public function it_puts_a_new_field_at_the_end_of_the_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        Field::factory()->create(['section_version_id' => $version->id, 'position' => 4]);

        $field = new CreateField(user: $user, sectionVersion: $version, type: FieldTypeEnum::Text, label: 'Street')->execute();

        $this->assertEquals(5, $field->position);
    }

    #[Test]
    public function it_numbers_what_sits_inside_a_repeater_separately(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        Field::factory()->create(['section_version_id' => $version->id, 'position' => 4]);
        $repeater = Field::factory()->repeater()->create(['section_version_id' => $version->id, 'position' => 5]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Phone,
            label: 'Number',
            parent: $repeater,
        )->execute();

        $this->assertEquals(1, $field->position);
        $this->assertEquals($repeater->id, $field->parent_field_id);
    }

    #[Test]
    public function it_refuses_a_repeater_inside_another_repeater(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $repeater = Field::factory()->repeater()->create(['section_version_id' => $version->id]);

        $this->expectException(CannotNestARepeater::class);

        new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Repeater,
            label: 'More phones',
            parent: $repeater,
        )->execute();
    }

    #[Test]
    public function it_refuses_to_put_a_field_inside_something_that_is_not_a_repeater(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $text = Field::factory()->create(['section_version_id' => $version->id]);

        $this->expectException(InvalidArgumentException::class);

        new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Phone,
            label: 'Number',
            parent: $text,
        )->execute();
    }

    #[Test]
    public function it_marks_a_repeater_as_repeatable_without_being_asked(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Repeater,
            label: 'Phone numbers',
            repeatMin: 1,
            repeatMax: 5,
        )->execute();

        $this->assertTrue($field->is_repeatable);
        $this->assertEquals(1, $field->repeat_min);
        $this->assertEquals(5, $field->repeat_max);
    }

    #[Test]
    public function it_takes_the_end_a_repeater_collects_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Repeater,
            label: 'Notes',
            repetitionOrder: RepetitionOrderEnum::NewestFirst,
        )->execute();

        $this->assertSame(RepetitionOrderEnum::NewestFirst, $field->refresh()->repetition_order);
    }

    #[Test]
    public function it_says_nothing_about_the_end_when_nobody_chose_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Repeater,
            label: 'Documents',
        )->execute();

        $this->assertNull($field->refresh()->repetition_order);
    }

    #[Test]
    public function it_drops_the_end_on_a_field_that_is_not_a_repeater(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Textarea,
            label: 'A note',
            repetitionOrder: RepetitionOrderEnum::NewestFirst,
        )->execute();

        $this->assertNull($field->refresh()->repetition_order);
    }

    #[Test]
    public function it_drops_repetition_limits_on_a_field_that_is_not_a_repeater(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Text,
            label: 'Street',
            repeatMin: 1,
            repeatMax: 5,
        )->execute();

        $this->assertFalse($field->is_repeatable);
        $this->assertNull($field->repeat_min);
        $this->assertNull($field->repeat_max);
    }

    #[Test]
    public function it_reads_choices_from_a_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Select,
            label: 'Gender',
            valueList: $list,
        )->execute();

        $this->assertEquals($list->id, $field->value_list_id);
    }

    #[Test]
    public function it_refuses_a_choice_field_with_no_list_to_offer(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Select,
            label: 'Gender',
        )->execute();
    }

    #[Test]
    public function it_drops_a_list_from_a_field_that_offers_no_choices(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Text,
            label: 'Street',
            valueList: $list,
        )->execute();

        $this->assertNull($field->value_list_id);
    }

    #[Test]
    public function it_keeps_the_list_a_phone_takes_its_labels_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Phone,
            label: 'Phone',
            valueList: $list,
        )->execute();

        $this->assertEquals($list->id, $field->value_list_id);
    }

    #[Test]
    public function it_takes_an_email_that_names_no_list_at_all(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Email,
            label: 'Email',
        )->execute();

        $this->assertNull($field->value_list_id);
    }

    #[Test]
    public function it_keeps_the_list_an_address_takes_its_labels_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Address,
            label: 'Address',
            valueList: $list,
        )->execute();

        $this->assertEquals($list->id, $field->value_list_id);
    }

    #[Test]
    public function it_takes_an_address_that_names_no_list_at_all(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Address,
            label: 'Address',
        )->execute();

        $this->assertNull($field->value_list_id);
    }

    #[Test]
    public function it_points_a_relation_at_contacts_unless_told_otherwise(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Relation,
            label: 'Owner',
        )->execute();

        $this->assertEquals(RelationTargetEnum::Contact, $field->relation_target);
        $this->assertNull($field->relation_entity_type_id);
    }

    #[Test]
    public function it_refuses_a_relation_pointing_at_an_entity_without_saying_which_kind(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Relation,
            label: 'Their animal',
            relationTarget: RelationTargetEnum::Entity,
        )->execute();
    }

    #[Test]
    public function it_points_a_relation_at_a_kind_of_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Relation,
            label: 'Their animal',
            relationTarget: RelationTargetEnum::Entity,
            relationEntityType: $entityType,
        )->execute();

        $this->assertEquals($entityType->id, $field->relation_entity_type_id);
    }

    #[Test]
    public function it_refuses_an_image_field_taking_something_that_is_not_an_image(): void
    {
        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(AcceptedFileKindIsNotValid::class);

        new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Image,
            label: 'Photo',
            options: ['accepted_mime_types' => ['application/pdf']],
        )->execute();
    }

    #[Test]
    public function it_drops_what_a_field_points_at_when_it_is_not_a_relation(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Text,
            label: 'Street',
            relationTarget: RelationTargetEnum::Entity,
            relationEntityType: $entityType,
        )->execute();

        $this->assertNull($field->relation_target);
        $this->assertNull($field->relation_entity_type_id);
    }

    #[Test]
    public function it_lets_the_type_alone_say_whether_a_field_drives_a_reminder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $recurring = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::RecurringDate,
            label: 'Birthday',
        )->execute();

        $plain = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Date,
            label: 'Met on',
        )->execute();

        $text = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Text,
            label: 'Diet',
        )->execute();

        $this->assertTrue($recurring->type->canDriveAReminder());
        $this->assertTrue($plain->type->canDriveAReminder());
        $this->assertFalse($text->type->canDriveAReminder());
    }

    #[Test]
    public function it_refuses_to_add_a_field_to_a_published_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new CreateField(user: $user, sectionVersion: $version, type: FieldTypeEnum::Text, label: 'Street')->execute();
    }

    #[Test]
    public function it_refuses_a_section_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $version = SectionVersion::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateField(user: $michael, sectionVersion: $version, type: FieldTypeEnum::Text, label: 'Street')->execute();
    }

    #[Test]
    public function it_refuses_a_list_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $michael->account_id]);
        $list = ValueList::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateField(
            user: $michael,
            sectionVersion: $version,
            type: FieldTypeEnum::Select,
            label: 'Gender',
            valueList: $list,
        )->execute();
    }

    #[Test]
    public function it_strips_html_out_of_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $field = new CreateField(
            user: $user,
            sectionVersion: $version,
            type: FieldTypeEnum::Text,
            label: '<b>Street</b>',
        )->execute();

        $this->assertEquals('Street', $field->label);
    }
}
