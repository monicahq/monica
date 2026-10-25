<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SetFieldValue;
use App\Actions\SetFieldValues;
use App\Enums\CountryEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\RepetitionOrderEnum;
use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Exceptions\ReminderNeedsADate;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\LifeEvent;
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
    public function it_writes_one_entry_in_the_journal_for_the_whole_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $city = $this->fieldOn($contact, ['key' => 'city']);
        $notes = $this->fieldOn($contact, ['key' => 'notes', 'type' => FieldTypeEnum::Textarea], beside: $city->sectionVersion);
        $documents = $this->fieldOn($contact, ['key' => 'documents', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true], beside: $city->sectionVersion);
        $description = $this->childOf($documents, ['key' => 'description']);
        $section = $this->sectionOf($city);
        FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'field_id' => $notes->id,
            'value_text' => 'Draws on the side.',
        ]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [
                $city->id => 'Scranton',
                $notes->id => '',
                $documents->id => [['values' => [$description->id => 'The lease']]],
            ],
        )->execute();

        Queue::assertPushed(LogUserAction::class, 1);
        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::FieldValuesUpdate
                && $job->vault->id === $contact->vault_id
                && $job->isAbout($contact)
                && $job->parameters === ['section' => $section->sectionVersion->section->name, 'name' => 'Pam Beesly'],
        );
    }

    #[Test]
    public function it_writes_nothing_in_the_journal_when_nothing_changed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $city = $this->fieldOn($contact, ['key' => 'city']);

        new SetFieldValues(user: $user, record: $contact, section: $this->sectionOf($city), values: [$city->id => 'Scranton'])->execute();

        Queue::fake();

        new SetFieldValues(user: $user, record: $contact, section: $this->sectionOf($city), values: [$city->id => 'Scranton'])->execute();

        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_moves_the_record_once_for_the_whole_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $city = $this->fieldOn($contact, ['key' => 'city']);
        $notes = $this->fieldOn($contact, ['key' => 'notes', 'type' => FieldTypeEnum::Textarea], beside: $city->sectionVersion);
        $contact->forceFill(['updated_at' => CarbonImmutable::now()->subYear()])->saveQuietly();
        $saves = 0;

        Contact::saved(function () use (&$saves): void {
            $saves++;
        });

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($city),
            values: [$city->id => 'Scranton', $notes->id => 'Sells paper.'],
        )->execute();

        $this->assertSame(1, $saves);
        $this->assertTrue($contact->refresh()->updated_at->isToday());
    }

    #[Test]
    public function it_leaves_the_record_alone_when_nothing_changed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $city = $this->fieldOn($contact, ['key' => 'city']);

        new SetFieldValues(user: $user, record: $contact, section: $this->sectionOf($city), values: [$city->id => 'Scranton'])->execute();

        $contact->forceFill(['updated_at' => CarbonImmutable::now()->subYear()])->saveQuietly();

        new SetFieldValues(user: $user, record: $contact, section: $this->sectionOf($city), values: [$city->id => 'Scranton'])->execute();

        $this->assertFalse($contact->refresh()->updated_at->isToday());
    }

    #[Test]
    public function it_writes_the_values_of_a_life_event(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $lifeEventType = $this->lifeEventTypeAsking($user, 'Sales call', [
            'Client' => FieldTypeEnum::Text,
            'Reams sold' => FieldTypeEnum::Number,
        ]);
        $lifeEvent = LifeEvent::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'life_event_type_id' => $lifeEventType->id,
            'structure_version_id' => $lifeEventType->structure->published_version_id,
        ]);
        $section = StructureVersionSection::query()->where('structure_version_id', $lifeEvent->structure_version_id)->firstOrFail();
        $client = $section->sectionVersion->fields->firstWhere('label', 'Client');
        $reams = $section->sectionVersion->fields->firstWhere('label', 'Reams sold');

        new SetFieldValues(
            user: $user,
            record: $lifeEvent,
            section: $section,
            values: [$client->id => 'Blue Cross of Pennsylvania', $reams->id => '40'],
        )->execute();

        $this->assertDatabaseHas('field_values', [
            'valuable_type' => LifeEvent::class,
            'valuable_id' => $lifeEvent->id,
            'field_id' => $client->id,
            'value_string' => 'Blue Cross of Pennsylvania',
        ]);
        $this->assertDatabaseHas('field_values', [
            'valuable_type' => LifeEvent::class,
            'valuable_id' => $lifeEvent->id,
            'field_id' => $reams->id,
        ]);
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
    public function it_refuses_an_item_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::Select, 'value_list_id' => $list->id]);
        $theirs = ValueListItem::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($field),
            values: [$field->id => (string) $theirs->id],
        )->execute();
    }

    #[Test]
    public function it_refuses_a_multiple_choice_naming_an_item_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $mine = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::Multiselect, 'value_list_id' => $list->id]);
        $theirs = ValueListItem::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($field),
            values: [$field->id => [(string) $mine->id, (string) $theirs->id]],
        )->execute();
    }

    #[Test]
    public function it_writes_the_label_beside_the_number_it_belongs_to(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $mobile = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::Phone, 'value_list_id' => $list->id]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($field),
            values: [$field->id => '+44 7700 900461'],
            labels: [$field->id => (string) $mobile->id],
        )->execute();

        $this->assertDatabaseHas('field_values', [
            'field_id' => $field->id,
            'value_string' => '+44 7700 900461',
            'value_list_item_id' => $mobile->id,
        ]);
    }

    #[Test]
    public function it_writes_the_wording_somebody_typed_for_the_open_label(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $other = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'key' => ValueListItem::OPEN_KEY,
        ]);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::Phone, 'value_list_id' => $list->id]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($field),
            values: [$field->id => '+44 7700 900461'],
            labels: [$field->id => (string) $other->id],
            labelWordings: [$field->id => 'Boat'],
        )->execute();

        $this->assertDatabaseHas('field_values', [
            'field_id' => $field->id,
            'value_list_item_id' => $other->id,
            'label_wording' => 'Boat',
        ]);
    }

    #[Test]
    public function it_keeps_a_field_unfilled_when_a_label_arrives_with_no_value(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $mobile = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id]);
        $field = $this->fieldOn($contact, ['type' => FieldTypeEnum::Phone, 'value_list_id' => $list->id]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($field),
            values: [$field->id => ''],
            labels: [$field->id => (string) $mobile->id],
        )->execute();

        $this->assertDatabaseMissing('field_values', ['field_id' => $field->id]);
    }

    #[Test]
    public function it_takes_a_required_email_with_no_label_at_all(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $field = $this->fieldOn($contact, [
            'type' => FieldTypeEnum::Email,
            'value_list_id' => $list->id,
            'is_required' => true,
        ]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($field),
            values: [$field->id => 'pam@dundermifflin.example'],
        )->execute();

        $this->assertDatabaseHas('field_values', [
            'field_id' => $field->id,
            'value_string' => 'pam@dundermifflin.example',
            'value_list_item_id' => null,
        ]);
    }

    #[Test]
    public function it_clears_an_address_whose_seven_boxes_came_back_empty(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $address = $this->fieldOn($contact, ['key' => 'address', 'type' => FieldTypeEnum::Address]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($address),
            values: [$address->id => ['street' => '1725 Slough Avenue', 'city' => 'Scranton']],
        )->execute();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($address),
            values: [$address->id => array_fill_keys(FieldValue::ADDRESS_PARTS, ' ')],
        )->execute();

        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_keeps_an_address_unfilled_when_a_label_arrives_with_seven_empty_boxes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $home = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Home']);
        $address = $this->fieldOn($contact, ['type' => FieldTypeEnum::Address, 'value_list_id' => $list->id]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($address),
            values: [$address->id => array_fill_keys(FieldValue::ADDRESS_PARTS, '')],
            labels: [$address->id => (string) $home->id],
        )->execute();

        $this->assertDatabaseMissing('field_values', ['field_id' => $address->id]);
        $this->assertSame(0, $home->fresh()->usage_count);
    }

    #[Test]
    public function it_refuses_a_required_address_whose_seven_boxes_are_empty(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $address = $this->fieldOn($contact, ['type' => FieldTypeEnum::Address, 'is_required' => true, 'label' => 'Address']);

        $this->expectException(FieldIsRequired::class);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($address),
            values: [$address->id => array_fill_keys(FieldValue::ADDRESS_PARTS, '')],
        )->execute();
    }

    #[Test]
    public function it_takes_a_required_address_with_one_box_filled_in_and_writes_the_rest_empty(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $address = $this->fieldOn($contact, ['type' => FieldTypeEnum::Address, 'is_required' => true]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($address),
            values: [$address->id => array_merge(array_fill_keys(FieldValue::ADDRESS_PARTS, ''), ['city' => 'Scranton'])],
        )->execute();

        $this->assertEquals(
            ['po_box' => '', 'extended' => '', 'street' => '', 'city' => 'Scranton', 'region' => '', 'postal_code' => '', 'country' => ''],
            FieldValue::query()->where('field_id', $address->id)->sole()->value_json,
        );
    }

    #[Test]
    public function it_writes_the_country_picked_as_the_wording_of_its_item(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $canada = $this->countryOf($user, CountryEnum::Canada);
        $address = $this->fieldOn($contact, ['type' => FieldTypeEnum::Address]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($address),
            values: [$address->id => ['city' => 'Toronto', 'country' => (string) $canada->id]],
        )->execute();

        $this->assertSame('Canada', FieldValue::query()->where('field_id', $address->id)->sole()->value_json['country']);
    }

    #[Test]
    public function it_keeps_the_country_a_device_wrote_when_asked_to_keep_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $address = $this->fieldOn($contact, ['type' => FieldTypeEnum::Address]);

        new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $address,
            value: ['city' => 'Scranton', 'country' => 'USA'],
        )->execute();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($address),
            values: [$address->id => ['city' => 'Stamford', 'country' => 'keep']],
        )->execute();

        $stored = FieldValue::query()->where('field_id', $address->id)->sole()->value_json;

        $this->assertSame('Stamford', $stored['city']);
        $this->assertSame('USA', $stored['country']);
    }

    #[Test]
    public function it_writes_no_country_when_asked_to_keep_one_that_was_never_there(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $address = $this->fieldOn($contact, ['type' => FieldTypeEnum::Address]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($address),
            values: [$address->id => ['city' => 'Scranton', 'country' => 'keep']],
        )->execute();

        $this->assertSame('', FieldValue::query()->where('field_id', $address->id)->sole()->value_json['country']);
    }

    #[Test]
    public function it_writes_no_country_when_none_was_picked(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $address = $this->fieldOn($contact, ['type' => FieldTypeEnum::Address]);

        new SetFieldValue(
            user: $user,
            valuable: $contact,
            field: $address,
            value: ['city' => 'Scranton', 'country' => 'USA'],
        )->execute();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($address),
            values: [$address->id => ['city' => 'Scranton', 'country' => '']],
        )->execute();

        $this->assertSame('', FieldValue::query()->where('field_id', $address->id)->sole()->value_json['country']);
    }

    #[Test]
    public function it_refuses_a_country_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $this->countryOf($user, CountryEnum::Canada);
        $theirs = $this->countryOf(User::factory()->create(), CountryEnum::France);
        $address = $this->fieldOn($contact, ['type' => FieldTypeEnum::Address]);

        $this->expectException(ModelNotFoundException::class);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($address),
            values: [$address->id => ['city' => 'Paris', 'country' => (string) $theirs->id]],
        )->execute();
    }

    #[Test]
    public function it_refuses_a_country_that_is_not_on_the_list_of_countries(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $this->countryOf($user, CountryEnum::Canada);
        $phoneLabels = ValueList::factory()->supplied(ValueListEnum::PhoneLabels)->create(['account_id' => $user->account_id]);
        $mobile = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $phoneLabels->id]);
        $address = $this->fieldOn($contact, ['type' => FieldTypeEnum::Address]);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($address),
            values: [$address->id => ['city' => 'Scranton', 'country' => (string) $mobile->id]],
        )->execute();
    }

    #[Test]
    public function it_refuses_a_country_typed_in_rather_than_picked(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $address = $this->fieldOn($contact, ['type' => FieldTypeEnum::Address]);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($address),
            values: [$address->id => ['city' => 'Scranton', 'country' => 'Canada']],
        )->execute();
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
        $avatar = $this->fieldOn($contact, ['type' => FieldTypeEnum::Avatar, 'is_required' => true]);

        $written = new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($avatar),
            values: [$avatar->id => 'anything'],
        )->execute();

        $this->assertSame(0, $written);
        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_writes_a_relation_to_somebody_by_their_uuid(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $manager = $this->fieldOn($contact, ['type' => FieldTypeEnum::Relation, 'relation_target' => RelationTargetEnum::Contact]);
        $michael = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $contact->vault_id]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($manager),
            values: [$manager->id => $michael->uuid],
        )->execute();

        $this->assertDatabaseHas('field_values', ['field_id' => $manager->id, 'related_contact_id' => $michael->id]);
    }

    #[Test]
    public function it_writes_a_relation_to_a_record_by_its_uuid(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $animals = EntityType::factory()->create(['account_id' => $user->account_id]);
        $pet = $this->fieldOn($contact, [
            'type' => FieldTypeEnum::Relation,
            'relation_target' => RelationTargetEnum::Entity,
            'relation_entity_type_id' => $animals->id,
        ]);
        $sprinkles = Entity::factory()->create(['vault_id' => $contact->vault_id, 'entity_type_id' => $animals->id]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($pet),
            values: [$pet->id => $sprinkles->uuid],
        )->execute();

        $this->assertDatabaseHas('field_values', ['field_id' => $pet->id, 'related_entity_id' => $sprinkles->id]);
    }

    #[Test]
    public function it_refuses_a_relation_to_somebody_of_another_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $manager = $this->fieldOn($contact, ['type' => FieldTypeEnum::Relation, 'relation_target' => RelationTargetEnum::Contact]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $karen = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $elsewhere->id]);

        $this->expectException(InvalidArgumentException::class);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($manager),
            values: [$manager->id => $karen->uuid],
        )->execute();
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
    public function it_writes_each_repetition_the_label_that_belongs_to_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $work = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Work']);
        $home = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Home']);
        $addresses = $this->fieldOn($contact, ['key' => 'addresses', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $email = $this->childOf($addresses, [
            'key' => 'email',
            'type' => FieldTypeEnum::Email,
            'value_list_id' => $list->id,
        ]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($addresses),
            values: [$addresses->id => [
                ['values' => [$email->id => 'michael@work.example'], 'labels' => [$email->id => (string) $work->id]],
                ['values' => [$email->id => 'michael@home.example'], 'labels' => [$email->id => (string) $home->id]],
            ]],
        )->execute();

        $this->assertDatabaseHas('field_values', [
            'field_id' => $email->id,
            'value_string' => 'michael@work.example',
            'value_list_item_id' => $work->id,
        ]);
        $this->assertDatabaseHas('field_values', [
            'field_id' => $email->id,
            'value_string' => 'michael@home.example',
            'value_list_item_id' => $home->id,
        ]);
    }

    #[Test]
    public function it_writes_each_repeated_address_with_the_label_that_belongs_to_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $home = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Home']);
        $holidayHome = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Holiday home']);
        $addresses = $this->fieldOn($contact, ['key' => 'addresses', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $address = $this->childOf($addresses, ['key' => 'address', 'type' => FieldTypeEnum::Address, 'value_list_id' => $list->id]);
        $section = $this->sectionOf($addresses);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$addresses->id => [
                ['values' => [$address->id => ['street' => '1725 Slough Avenue', 'city' => 'Scranton']], 'labels' => [$address->id => (string) $home->id]],
                ['values' => [$address->id => ['street' => 'Schrute Farms', 'city' => 'Honesdale']], 'labels' => [$address->id => (string) $holidayHome->id]],
                ['values' => [$address->id => array_fill_keys(FieldValue::ADDRESS_PARTS, '')], 'labels' => [$address->id => (string) $home->id]],
            ]],
        )->execute();

        $rows = FieldValueRow::query()->orderBy('position')->get();

        $this->assertCount(2, $rows);
        $this->assertSame('1725 Slough Avenue', FieldValue::query()->where('field_value_row_id', $rows[0]->id)->sole()->value_json['street']);
        $this->assertSame($home->id, FieldValue::query()->where('field_value_row_id', $rows[0]->id)->sole()->value_list_item_id);
        $this->assertSame('Schrute Farms', FieldValue::query()->where('field_value_row_id', $rows[1]->id)->sole()->value_json['street']);
        $this->assertSame($holidayHome->id, FieldValue::query()->where('field_value_row_id', $rows[1]->id)->sole()->value_list_item_id);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$addresses->id => [
                ['id' => $rows[1]->id, 'values' => [$address->id => ['street' => 'Schrute Farms', 'city' => 'Honesdale']], 'labels' => [$address->id => (string) $holidayHome->id]],
            ]],
        )->execute();

        $this->assertSame(1, FieldValueRow::query()->count());
        $this->assertModelMissing($rows[0]);
        $this->assertSame('Honesdale', FieldValue::query()->where('field_value_row_id', $rows[1]->id)->sole()->value_json['city']);
    }

    #[Test]
    public function it_keeps_the_country_of_the_repetition_it_is_asked_to_keep_it_in(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $addresses = $this->fieldOn($contact, ['key' => 'addresses', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $address = $this->childOf($addresses, ['key' => 'address', 'type' => FieldTypeEnum::Address]);
        $section = $this->sectionOf($addresses);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$addresses->id => [
                ['values' => [$address->id => ['city' => 'Scranton']]],
                ['values' => [$address->id => ['city' => 'Winnipeg']]],
            ]],
        )->execute();

        $rows = FieldValueRow::query()->orderBy('position')->get();
        $first = FieldValue::query()->where('field_value_row_id', $rows[0]->id)->sole();
        $second = FieldValue::query()->where('field_value_row_id', $rows[1]->id)->sole();
        $first->update(['value_json' => [...$first->value_json, 'country' => 'USA']]);
        $second->update(['value_json' => [...$second->value_json, 'country' => 'Canada']]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$addresses->id => [
                ['id' => $rows[0]->id, 'values' => [$address->id => ['city' => 'Stamford', 'country' => 'keep']]],
                ['id' => $rows[1]->id, 'values' => [$address->id => ['city' => 'Winnipeg', 'country' => 'keep']]],
            ]],
        )->execute();

        $this->assertSame('USA', $first->fresh()->value_json['country']);
        $this->assertSame('Stamford', $first->fresh()->value_json['city']);
        $this->assertSame('Canada', $second->fresh()->value_json['country']);
    }

    #[Test]
    public function it_refuses_a_row_missing_a_required_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $caregivers = $this->fieldOn($contact, ['key' => 'caregivers', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $person = $this->childOf($caregivers, ['key' => 'caregiver_person', 'label' => 'Person', 'is_required' => true]);
        $role = $this->childOf($caregivers, ['key' => 'caregiver_role']);

        $this->expectException(FieldIsRequired::class);
        $this->expectExceptionMessage('Person has to be filled in.');

        try {
            new SetFieldValues(
                user: $user,
                record: $contact,
                section: $this->sectionOf($caregivers),
                values: [$caregivers->id => [['values' => [$person->id => '', $role->id => 'Pet sitter']]]],
            )->execute();
        } finally {
            $this->assertSame(0, FieldValueRow::query()->count());
        }
    }

    #[Test]
    public function it_refuses_a_caregiver_row_that_names_nobody(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $caregivers = $this->fieldOn($contact, ['key' => 'caregivers', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $this->childOf($caregivers, [
            'key' => 'caregiver_person',
            'label' => 'Person',
            'type' => FieldTypeEnum::Relation,
            'relation_target' => RelationTargetEnum::Contact,
            'is_required' => true,
        ]);
        $role = $this->childOf($caregivers, ['key' => 'caregiver_role']);

        $this->expectException(FieldIsRequired::class);
        $this->expectExceptionMessage('Person has to be filled in.');

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($caregivers),
            values: [$caregivers->id => [['values' => [$role->id => 'Pet sitter']]]],
        )->execute();
    }

    #[Test]
    public function it_writes_the_person_each_caregiver_row_names(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $caregivers = $this->fieldOn($contact, ['key' => 'caregivers', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $person = $this->childOf($caregivers, ['key' => 'caregiver_person', 'type' => FieldTypeEnum::Relation, 'relation_target' => RelationTargetEnum::Contact]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $contact->vault_id]);
        $angela = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $contact->vault_id]);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($caregivers),
            values: [$caregivers->id => [
                ['values' => [$person->id => $dwight->uuid]],
                ['values' => [$person->id => $angela->uuid]],
            ]],
        )->execute();

        $this->assertSame(2, FieldValueRow::query()->count());
        $this->assertDatabaseHas('field_values', ['field_id' => $person->id, 'related_contact_id' => $dwight->id]);
        $this->assertDatabaseHas('field_values', ['field_id' => $person->id, 'related_contact_id' => $angela->id]);
    }

    #[Test]
    public function it_refuses_the_same_caregiver_twice_in_a_list_that_asks_for_distinct_people(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $caregivers = $this->fieldOn($contact, ['key' => 'caregivers', 'type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $person = $this->childOf($caregivers, [
            'key' => 'caregiver_person',
            'type' => FieldTypeEnum::Relation,
            'relation_target' => RelationTargetEnum::Contact,
            'options' => ['distinct_in_list' => true],
        ]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $contact->vault_id]);

        $this->expectException(FieldValueIsNotUnique::class);

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $this->sectionOf($caregivers),
            values: [$caregivers->id => [
                ['values' => [$person->id => $dwight->uuid]],
                ['values' => [$person->id => $dwight->uuid]],
            ]],
        )->execute();
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
        $nextMonth = now()->addMonth()->toDateString();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$field->id => $nextMonth],
            reminders: [$field->id => $this->asked()],
        )->execute();

        new SetFieldValues(
            user: $user,
            record: $contact,
            section: $section,
            values: [$field->id => $nextMonth],
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

    private function countryOf(User $user, CountryEnum $country): ValueListItem
    {
        $countries = ValueList::query()
            ->where('account_id', $user->account_id)
            ->where('key', ValueListEnum::Countries->value)
            ->first()
            ?? ValueList::factory()->supplied(ValueListEnum::Countries)->create(['account_id' => $user->account_id]);

        return ValueListItem::factory()->supplied($country)->create([
            'account_id' => $user->account_id,
            'value_list_id' => $countries->id,
        ]);
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
                'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::SourceChanged->value,
            ]);
        }

        $this->assertDatabaseHas('reminder_occurrences', [
            'local_date' => '2027-02-10',
            'cancelled_at' => null,
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
            ->whereNotNull('cancelled_at')
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
            ->whereNull('cancelled_at')
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
            ->where('cancelled_reason', '!=', ReminderOccurrenceCancelledReasonEnum::RuleDeleted->value)
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
