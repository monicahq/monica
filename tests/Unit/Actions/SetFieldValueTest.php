<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SetFieldValue;
use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\FieldValueIsNotUnique;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetFieldValueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_value_into_the_column_the_type_of_the_field_points_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOf($user, ['key' => 'city', 'label' => 'City']);

        $value = new SetFieldValue(user: $user, valuable: $contact, field: $field, value: 'Scranton')->execute();

        $this->assertInstanceOf(FieldValue::class, $value);
        $this->assertDatabaseHas('field_values', [
            'id' => $value->id,
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'field_key' => 'city',
            'value_string' => 'Scranton',
            'is_detached' => false,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::FieldValueSet
                && $job->vault->id === $contact->vault_id
                && $job->isAbout($value)
                && $job->parameters === ['label' => 'City', 'name' => 'Pam Beesly'],
        );
    }

    #[Test]
    public function it_stores_an_ampersand_in_a_text_value_as_typed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOf($user, ['key' => 'city', 'label' => 'City']);

        $value = new SetFieldValue(user: $user, valuable: $contact, field: $field, value: 'Dunder & Mifflin')->execute();

        $this->assertDatabaseHas('field_values', [
            'id' => $value->id,
            'value_string' => 'Dunder & Mifflin',
        ]);
    }

    #[Test]
    public function it_writes_nothing_in_the_journal_when_asked_not_to(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOf($user, ['key' => 'city']);

        new SetFieldValue(user: $user, valuable: $contact, field: $field, value: 'Scranton', writesToTheJournal: false)->execute();

        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_leaves_the_value_lists_alone_when_no_item_is_chosen(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOf($user, ['key' => 'city']);
        $statements = [];

        DB::listen(function (QueryExecuted $query) use (&$statements): void {
            $statements[] = $query->sql;
        });

        new SetFieldValue(user: $user, valuable: $contact, field: $field, value: 'Scranton')->execute();

        $this->assertSame([], array_filter($statements, fn (string $sql): bool => str_contains($sql, 'value_list_items')));
    }

    #[Test]
    public function it_writes_each_type_into_its_own_column(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $long = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $this->fieldOf($user, ['type' => FieldTypeEnum::Textarea]),
            value: 'Sells paper in Scranton.',
        )->execute();
        $number = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $this->fieldOf($user, ['type' => FieldTypeEnum::Number]),
            value: 42,
        )->execute();
        $duration = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $this->fieldOf($user, ['type' => FieldTypeEnum::Duration]),
            value: 90,
        )->execute();
        $yesNo = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $this->fieldOf($user, ['type' => FieldTypeEnum::Boolean]),
            value: true,
        )->execute();
        $date = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $this->fieldOf($user, ['type' => FieldTypeEnum::Date]),
            value: '2005-03-24',
        )->execute();
        $address = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $this->fieldOf($user, ['type' => FieldTypeEnum::Address]),
            value: ['street' => '1725 Slough Avenue', 'city' => 'Scranton'],
        )->execute();

        $this->assertEquals('Sells paper in Scranton.', $long->value_text);
        $this->assertEquals('42.000000', $number->value_number);
        $this->assertEquals(90, (int) $duration->value_number);
        $this->assertTrue($yesNo->value_boolean);
        $this->assertEquals('2005-03-24', $date->value_date->format('Y-m-d'));
        $this->assertEquals('1725 Slough Avenue', $address->value_json['street']);
        $this->assertEquals('Scranton', $address->value_json['city']);
    }

    #[Test]
    public function it_writes_an_address_as_the_seven_parts_a_card_carries_in_the_order_a_card_writes_them(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $this->fieldOf($user, ['type' => FieldTypeEnum::Address]),
            value: ['city' => 'Scranton', 'street' => '1725 Slough Avenue', 'region' => null, 'postal_code' => '18505'],
        )->execute();

        $this->assertSame([
            'po_box' => '',
            'extended' => '',
            'street' => '1725 Slough Avenue',
            'city' => 'Scranton',
            'region' => '',
            'postal_code' => '18505',
            'country' => '',
        ], $value->value_json);
    }

    #[Test]
    public function it_strips_the_markup_out_of_every_part_of_an_address(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $this->fieldOf($user, ['type' => FieldTypeEnum::Address]),
            value: ['street' => '<b>1725 Slough Avenue</b>', 'city' => '  <script>alert("x")</script>Scranton  '],
        )->execute();

        $this->assertSame('Scranton', $value->value_json['city']);
    }

    #[Test]
    public function it_writes_an_address_and_the_label_saying_what_it_is_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $work = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'key' => 'work',
            'label' => 'Work',
        ]);
        $field = $this->labelledFieldOf($user, $list, FieldTypeEnum::Address);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $field,
            value: ['street' => '1725 Slough Avenue', 'city' => 'Scranton'],
            valueListItem: $work,
        )->execute();

        $this->assertDatabaseHas('field_values', [
            'id' => $value->id,
            'value_list_item_id' => $work->id,
        ]);
        $this->assertSame('1725 Slough Avenue', $value->fresh()->value_json['street']);
        $this->assertSame(1, $work->fresh()->usage_count);
    }

    #[Test]
    public function it_lets_two_records_share_an_address_on_a_field_that_asks_for_unique_values(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $other = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
        ]);
        $field = $this->fieldOf($user, ['type' => FieldTypeEnum::Address, 'is_unique' => true]);

        new SetFieldValue(user: $user, valuable: $contact, field: $field, value: ['street' => '1725 Slough Avenue'])->execute();
        $second = new SetFieldValue(user: $user, valuable: $other, field: $field, value: ['street' => '1725 Slough Avenue'])->execute();

        $this->assertSame('1725 Slough Avenue', $second->value_json['street']);
    }

    #[Test]
    public function it_replaces_the_value_a_field_already_held_rather_than_adding_another(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOf($user);

        $first = new SetFieldValue(user: $user, valuable: $contact, field: $field, value: 'Scranton')->execute();
        $second = new SetFieldValue(user: $user, valuable: $contact, field: $field, value: 'Utica')->execute();

        $this->assertTrue($first->is($second));
        $this->assertEquals('Utica', $second->value_string);
        $this->assertSame(1, FieldValue::query()->count());
    }

    #[Test]
    public function it_leaves_no_trace_of_the_column_the_value_was_in_before(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOf($user);
        FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'value_string' => null,
            'value_text' => 'Left over',
        ]);

        $value = new SetFieldValue(user: $user, valuable: $contact, field: $field, value: 'Scranton')->execute();

        $this->assertEquals('Scranton', $value->value_string);
        $this->assertNull($value->value_text);
    }

    #[Test]
    public function it_brings_a_value_that_had_been_set_aside_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOf($user);
        FieldValue::factory()->detached()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
        ]);

        $value = new SetFieldValue(user: $user, valuable: $contact, field: $field, value: 'Scranton')->execute();

        $this->assertFalse($value->is_detached);
        $this->assertNull($value->detached_at);
    }

    #[Test]
    public function it_strips_the_markup_out_of_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $this->fieldOf($user),
            value: '<script>alert("Scranton")</script>Scranton',
        )->execute();

        $this->assertEquals('Scranton', $value->value_string);
    }

    #[Test]
    public function it_stores_a_date_whose_year_nobody_knows_under_a_placeholder_year(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->recurringDate()->create(['account_id' => $user->account_id]);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $field,
            value: '1964-03-15',
            yearIsUnknown: true,
        )->execute();

        $this->assertEquals(FieldValue::PLACEHOLDER_YEAR.'-03-15', $value->value_date->format('Y-m-d'));
        $this->assertTrue($value->year_is_unknown);
    }

    #[Test]
    public function it_keeps_the_twenty_ninth_of_february_when_nobody_knows_the_year(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->recurringDate()->create(['account_id' => $user->account_id]);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $field,
            value: '1980-02-29',
            yearIsUnknown: true,
        )->execute();

        $this->assertEquals('02-29', $value->value_date->format('m-d'));
    }

    #[Test]
    public function it_drops_a_missing_part_on_a_field_that_cannot_have_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOf($user, ['type' => FieldTypeEnum::Date]);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $field,
            value: '2005-03-24',
            yearIsUnknown: true,
            monthIsUnknown: true,
        )->execute();

        $this->assertEquals('2005-03-24', $value->value_date->format('Y-m-d'));
        $this->assertFalse($value->year_is_unknown);
        $this->assertFalse($value->month_is_unknown);
    }

    #[Test]
    public function it_writes_the_item_a_choice_field_points_at_and_counts_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $item = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Vegetarian',
        ]);
        $field = Field::factory()->choice($list)->create(['account_id' => $user->account_id]);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $field,
            valueListItem: $item,
        )->execute();

        $this->assertEquals($item->id, $value->value_list_item_id);
        $this->assertSame(1, $item->fresh()->usage_count);
    }

    #[Test]
    public function it_hands_the_count_over_when_the_choice_changes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $before = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        $after = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        $field = Field::factory()->choice($list)->create(['account_id' => $user->account_id]);

        new SetFieldValue(user: $user, valuable: $contact, field: $field, valueListItem: $before)->execute();
        new SetFieldValue(user: $user, valuable: $contact, field: $field, valueListItem: $after)->execute();

        $this->assertSame(0, $before->fresh()->usage_count);
        $this->assertSame(1, $after->fresh()->usage_count);
    }

    #[Test]
    public function it_writes_every_item_a_multiple_choice_field_points_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $first = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        $second = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        $field = Field::factory()->choice($list)->create([
            'account_id' => $user->account_id,
            'type' => FieldTypeEnum::Multiselect,
        ]);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $field,
            valueListItems: [$first, $second],
        )->execute();

        $this->assertEquals([$first->id, $second->id], $value->value_list_item_ids);
        $this->assertSame(1, $first->fresh()->usage_count);
        $this->assertSame(1, $second->fresh()->usage_count);
    }

    #[Test]
    public function it_refuses_an_item_that_is_not_on_the_list_the_field_offers(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->choice()->create(['account_id' => $user->account_id]);
        $item = ValueListItem::factory()->create(['account_id' => $user->account_id, 'label' => 'Vegan']);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValue(user: $user, valuable: $contact, field: $field, valueListItem: $item)->execute();
    }

    #[Test]
    public function it_writes_a_phone_number_and_the_label_saying_what_it_is_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $mobile = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Mobile',
        ]);
        $field = $this->labelledFieldOf($user, $list, FieldTypeEnum::Phone);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $field,
            value: '+44 7700 900461',
            valueListItem: $mobile,
        )->execute();

        $this->assertDatabaseHas('field_values', [
            'id' => $value->id,
            'value_string' => '+44 7700 900461',
            'value_list_item_id' => $mobile->id,
        ]);
        $this->assertSame(1, $mobile->fresh()->usage_count);
    }

    #[Test]
    public function it_takes_an_email_address_with_no_label_at_all(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $field = $this->labelledFieldOf($user, $list, FieldTypeEnum::Email);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $field,
            value: 'pam@dundermifflin.example',
        )->execute();

        $this->assertDatabaseHas('field_values', [
            'id' => $value->id,
            'value_string' => 'pam@dundermifflin.example',
            'value_list_item_id' => null,
        ]);
    }

    #[Test]
    public function it_refuses_a_label_that_is_not_on_the_list_the_field_names(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $field = $this->labelledFieldOf($user, $list, FieldTypeEnum::Phone);
        $elsewhere = ValueListItem::factory()->create(['account_id' => $user->account_id, 'label' => 'Fax']);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $field,
            value: '+44 7700 900461',
            valueListItem: $elsewhere,
        )->execute();
    }

    #[Test]
    public function it_keeps_the_wording_somebody_typed_beside_the_open_label(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $other = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'key' => ValueListItem::OPEN_KEY,
            'label' => 'Custom...',
        ]);
        $field = $this->labelledFieldOf($user, $list, FieldTypeEnum::Phone);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $field,
            value: '+44 7700 900461',
            valueListItem: $other,
            labelWording: 'Boat',
        )->execute();

        $this->assertDatabaseHas('field_values', [
            'id' => $value->id,
            'value_list_item_id' => $other->id,
            'label_wording' => 'Boat',
        ]);
    }

    #[Test]
    public function it_drops_a_wording_typed_beside_a_label_that_names_itself(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $mobile = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'key' => 'mobile',
            'label' => 'Mobile',
        ]);
        $field = $this->labelledFieldOf($user, $list, FieldTypeEnum::Phone);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $field,
            value: '+44 7700 900461',
            valueListItem: $mobile,
            labelWording: 'Boat',
        )->execute();

        $this->assertDatabaseHas('field_values', [
            'id' => $value->id,
            'value_list_item_id' => $mobile->id,
            'label_wording' => null,
        ]);
    }

    #[Test]
    public function it_refuses_a_repeated_number_whatever_labels_the_two_carry(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $other = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'first_name' => 'Jim',
            'last_name' => 'Halpert',
        ]);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $mobile = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Mobile']);
        $work = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Work']);
        $field = $this->labelledFieldOf($user, $list, FieldTypeEnum::Phone, ['is_unique' => true]);

        new SetFieldValue(user: $user, valuable: $contact, field: $field, value: '+44 7700 900461', valueListItem: $mobile)->execute();

        $this->expectException(FieldValueIsNotUnique::class);

        new SetFieldValue(user: $user, valuable: $other, field: $field, value: '+44 7700 900461', valueListItem: $work)->execute();
    }

    #[Test]
    public function it_writes_the_contact_a_relation_points_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $target = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'last_name' => 'Schrute',
        ]);
        $field = Field::factory()->relation()->create(['account_id' => $user->account_id]);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $field,
            relatedContact: $target,
        )->execute();

        $this->assertEquals($target->id, $value->related_contact_id);
        $this->assertNull($value->related_entity_id);
    }

    #[Test]
    public function it_writes_the_entity_a_relation_points_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);
        $target = Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'entity_type_id' => $type->id,
            'name' => 'Sprinkles',
        ]);
        $field = Field::factory()->relation()->create([
            'account_id' => $user->account_id,
            'relation_target' => RelationTargetEnum::Entity,
            'relation_entity_type_id' => $type->id,
        ]);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $field,
            relatedEntity: $target,
        )->execute();

        $this->assertEquals($target->id, $value->related_entity_id);
        $this->assertNull($value->related_contact_id);
    }

    #[Test]
    public function it_refuses_a_relation_pointing_at_another_kind_of_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $target = Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'name' => 'Sprinkles',
        ]);
        $field = Field::factory()->relation()->create([
            'account_id' => $user->account_id,
            'relation_target' => RelationTargetEnum::Entity,
            'relation_entity_type_id' => EntityType::factory()->create(['account_id' => $user->account_id])->id,
        ]);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValue(user: $user, valuable: $contact, field: $field, relatedEntity: $target)->execute();
    }

    #[Test]
    public function it_refuses_a_relation_crossing_a_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $elsewhere = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'last_name' => 'Schrute',
        ]);
        $field = Field::factory()->relation()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValue(user: $user, valuable: $contact, field: $field, relatedContact: $elsewhere)->execute();
    }

    #[Test]
    public function it_refuses_a_relation_pointing_at_nothing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->relation()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValue(user: $user, valuable: $contact, field: $field)->execute();
    }

    #[Test]
    public function it_says_that_one_more_thing_points_at_the_file_it_writes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $file = File::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'reference_count' => 0,
            'orphaned_at' => now(),
        ]);
        $field = $this->fieldOf($user, ['type' => FieldTypeEnum::File]);

        $value = new SetFieldValue(user: $user, valuable: $contact, field: $field, file: $file)->execute();

        $file->refresh();

        $this->assertEquals($file->id, $value->value_file_id);
        $this->assertSame(1, $file->reference_count);
        $this->assertNull($file->orphaned_at);
    }

    #[Test]
    public function it_hands_the_reference_over_when_the_file_is_replaced(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $before = File::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'reference_count' => 0,
        ]);
        $after = File::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'reference_count' => 0,
        ]);
        $field = $this->fieldOf($user, ['type' => FieldTypeEnum::Image]);

        new SetFieldValue(user: $user, valuable: $contact, field: $field, file: $before)->execute();
        new SetFieldValue(user: $user, valuable: $contact, field: $field, file: $after)->execute();

        $this->assertSame(0, $before->fresh()->reference_count);
        $this->assertNotNull($before->fresh()->orphaned_at);
        $this->assertSame(1, $after->fresh()->reference_count);
    }

    #[Test]
    public function it_writes_a_value_inside_a_repetition_of_a_repeater(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $repeater = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $child = $this->fieldOf($user, ['parent_field_id' => $repeater->id]);
        $row = $this->rowOn($user, $contact, $repeater);

        $value = new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $child,
            row: $row,
            value: '570-555-0123',
        )->execute();

        $this->assertEquals($row->id, $value->field_value_row_id);
    }

    #[Test]
    public function it_refuses_the_same_contact_twice_in_a_list_that_asks_for_distinct_people(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $contact->vault_id]);
        $repeater = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $person = Field::factory()->relation()->create([
            'account_id' => $user->account_id,
            'parent_field_id' => $repeater->id,
            'label' => 'Person',
            'options' => ['distinct_in_list' => true],
        ]);

        new SetFieldValue(user: $user, valuable: $contact, field: $person, row: $this->rowOn($user, $contact, $repeater, 1), relatedContact: $dwight)->execute();

        $this->expectException(FieldValueIsNotUnique::class);
        $this->expectExceptionMessage('Person is already in this list.');

        new SetFieldValue(user: $user, valuable: $contact, field: $person, row: $this->rowOn($user, $contact, $repeater, 2), relatedContact: $dwight)->execute();
    }

    #[Test]
    public function it_allows_the_same_contact_twice_without_the_setting(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $contact->vault_id]);
        $repeater = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $person = Field::factory()->relation()->create([
            'account_id' => $user->account_id,
            'parent_field_id' => $repeater->id,
        ]);

        new SetFieldValue(user: $user, valuable: $contact, field: $person, row: $this->rowOn($user, $contact, $repeater, 1), relatedContact: $dwight)->execute();
        new SetFieldValue(user: $user, valuable: $contact, field: $person, row: $this->rowOn($user, $contact, $repeater, 2), relatedContact: $dwight)->execute();

        $this->assertSame(2, FieldValue::query()->where('field_id', $person->id)->count());
    }

    #[Test]
    public function it_keeps_the_repetitions_of_the_same_field_apart(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $repeater = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $child = $this->fieldOf($user, ['parent_field_id' => $repeater->id]);
        $first = $this->rowOn($user, $contact, $repeater);
        $second = $this->rowOn($user, $contact, $repeater, 2);

        new SetFieldValue(user: $user, valuable: $contact, field: $child, row: $first, value: '570-555-0123')->execute();
        new SetFieldValue(user: $user, valuable: $contact, field: $child, row: $second, value: '570-555-9876')->execute();

        $this->assertSame(2, FieldValue::query()->count());
    }

    #[Test]
    public function it_refuses_a_field_inside_a_repeater_given_no_repetition(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $repeater = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $child = $this->fieldOf($user, ['parent_field_id' => $repeater->id]);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValue(user: $user, valuable: $contact, field: $child, value: '570-555-0123')->execute();
    }

    #[Test]
    public function it_refuses_a_field_outside_a_repeater_given_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $repeater = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $row = $this->rowOn($user, $contact, $repeater);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $this->fieldOf($user),
            row: $row,
            value: 'Scranton',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_repetition_belonging_to_another_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $somebodyElse = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'last_name' => 'Schrute',
        ]);
        $repeater = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $child = $this->fieldOf($user, ['parent_field_id' => $repeater->id]);
        $row = $this->rowOn($user, $somebodyElse, $repeater);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValue(user: $user, valuable: $contact, field: $child, row: $row, value: '570-555-0123')->execute();
    }

    #[Test]
    public function it_refuses_a_repetition_belonging_to_another_repeater(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $repeater = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $another = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $child = $this->fieldOf($user, ['parent_field_id' => $repeater->id]);
        $row = $this->rowOn($user, $contact, $another);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValue(user: $user, valuable: $contact, field: $child, row: $row, value: '570-555-0123')->execute();
    }

    #[Test]
    public function it_refuses_a_field_that_stores_nothing_at_all(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: Field::factory()->layout()->create(['account_id' => $user->account_id]),
            value: 'Scranton',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_repeater_given_a_value_of_its_own(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: Field::factory()->repeater()->create(['account_id' => $user->account_id]),
            value: 'Scranton',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_value_another_record_in_the_vault_already_holds(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $somebodyElse = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'last_name' => 'Schrute',
        ]);
        $field = $this->fieldOf($user, ['is_unique' => true, 'label' => 'Badge number']);

        new SetFieldValue(user: $user, valuable: $somebodyElse, field: $field, value: '1138')->execute();

        $this->expectException(FieldValueIsNotUnique::class);

        new SetFieldValue(user: $user, valuable: $contact, field: $field, value: '1138')->execute();
    }

    #[Test]
    public function it_lets_a_record_write_the_same_unique_value_over_itself(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOf($user, ['is_unique' => true]);

        new SetFieldValue(user: $user, valuable: $contact, field: $field, value: '1138')->execute();
        $value = new SetFieldValue(user: $user, valuable: $contact, field: $field, value: '1138')->execute();

        $this->assertEquals('1138', $value->value_string);
    }

    #[Test]
    public function it_lets_another_vault_hold_the_same_unique_value(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $elsewhere = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'last_name' => 'Schrute',
        ]);
        $field = $this->fieldOf($user, ['is_unique' => true]);

        new SetFieldValue(user: $user, valuable: $contact, field: $field, value: '1138')->execute();
        $value = new SetFieldValue(user: $user, valuable: $elsewhere, field: $field, value: '1138')->execute();

        $this->assertEquals('1138', $value->value_string);
    }

    #[Test]
    public function it_refuses_a_record_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new SetFieldValue(
            user: $user,
            valuable: Contact::factory()->create(),
            field: $this->fieldOf($user),
            value: 'Scranton',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_field_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new SetFieldValue(
            user: $user,
            valuable: $this->contactOf($user),
            field: Field::factory()->create(),
            value: 'Scranton',
        )->execute();
    }

    #[Test]
    public function it_moves_the_reminder_reading_a_date_that_moved(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->recurringDate()->create(['account_id' => $user->account_id]);
        $value = FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'value_string' => null,
            'value_date' => '1984-05-12',
        ]);
        $reminder = Reminder::factory()->for($value, 'fieldValue')->create(['timezone' => 'UTC']);
        $old = ReminderOccurrence::factory()->for($reminder)->create([
            'local_date' => '2027-05-12',
            'scheduled_for' => '2027-05-12 09:00:00',
            'generation_key' => '1:2027-05-12',
        ]);

        new SetFieldValue(user: $user, valuable: $contact, field: $field, value: '1984-05-20')->execute();

        $this->assertNotNull($old->refresh()->cancelled_at);
        $this->assertDatabaseHas('reminder_occurrences', [
            'reminder_id' => $reminder->id,
            'local_date' => '2027-05-20',
            'cancelled_at' => null,
        ]);
        $this->assertSame('2027-05-01 06:00:00', $reminder->refresh()->schedule_changed_at->format('Y-m-d H:i:s'));
    }

    private function contactOf(User $user): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
        ]);
    }

    /** @param  array<string, mixed>  $attributes */
    /** @param  array<string, mixed>  $attributes */
    private function labelledFieldOf(User $user, ValueList $list, FieldTypeEnum $type, array $attributes = []): Field
    {
        return Field::factory()->create(array_merge([
            'account_id' => $user->account_id,
            'type' => $type,
            'value_list_id' => $list->id,
        ], $attributes));
    }

    private function fieldOf(User $user, array $attributes = []): Field
    {
        return Field::factory()->create(array_merge(['account_id' => $user->account_id], $attributes));
    }

    private function rowOn(User $user, Contact $contact, Field $repeater, int $position = 1): FieldValueRow
    {
        return FieldValueRow::factory()->create([
            'account_id' => $user->account_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $repeater->id,
            'position' => $position,
        ]);
    }
}
