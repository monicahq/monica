<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateDefaultValueLists;
use App\Actions\MoveRecordToStructureVersion;
use App\Enums\DefaultFieldEnum;
use App\Enums\DefaultTemplateEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\SnapshotReasonEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\RecordSnapshot;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PDOException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MoveRecordToStructureVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_record_on_another_structure(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $to = $this->publishedContactVersion($user);

        $moved = new MoveRecordToStructureVersion(
            user: $user,
            record: $contact,
            structureVersion: $to,
        )->execute();

        $this->assertEquals($to->id, $moved->structure_version_id);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::RecordMovedToTemplate
                && $job->isAbout($contact)
                && $job->parameters === ['name' => 'Pam Beesly', 'template' => $to->structure->owner->name],
        );
    }

    #[Test]
    public function it_logs_a_move_within_one_template_as_a_new_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $to = StructureVersion::factory()->published()->create([
            'structure_id' => $contact->structureVersion->structure_id,
            'version_number' => 2,
        ]);

        new MoveRecordToStructureVersion(
            user: $user,
            record: $contact,
            structureVersion: $to,
        )->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::RecordMovedToTemplateVersion
                && $job->parameters === ['name' => 'Pam Beesly', 'version' => 2],
        );
    }

    #[Test]
    public function it_does_nothing_when_the_record_is_already_on_the_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        new MoveRecordToStructureVersion(
            user: $user,
            record: $contact,
            structureVersion: $contact->structureVersion,
        )->execute();

        $this->assertSame(0, RecordSnapshot::query()->count());
        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_writes_one_entry_when_the_same_move_arrives_twice_at_once(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $dwight = $this->contactOf($user);
        $from = $dwight->structureVersion;
        $to = $this->publishedContactVersion($user);

        $staleDwight = Contact::query()->findOrFail($dwight->id);

        $otherRequestHasRun = false;
        Event::listen(TransactionBeginning::class, function () use (&$otherRequestHasRun, $user, $dwight, $to): void {
            if ($otherRequestHasRun) {
                return;
            }

            $otherRequestHasRun = true;

            new MoveRecordToStructureVersion(user: $user, record: $dwight, structureVersion: $to)->execute();
        });

        new MoveRecordToStructureVersion(user: $user, record: $staleDwight, structureVersion: $to)->execute();

        $this->assertSame($to->id, $staleDwight->structure_version_id);
        $this->assertSame(1, RecordSnapshot::query()->count());
        $this->assertSame(0, $from->fresh()->record_count);
        $this->assertSame(1, $to->fresh()->record_count);
        $this->assertCount(1, Queue::pushed(
            LogUserAction::class,
            fn (LogUserAction $job): bool => $job->action === UserActionEnum::RecordMovedToTemplate,
        ));
    }

    #[Test]
    public function it_writes_nothing_to_the_log_when_told_not_to(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $to = $this->publishedContactVersion($user);

        new MoveRecordToStructureVersion(
            user: $user,
            record: $contact,
            structureVersion: $to,
            writesToLog: false,
        )->execute();

        $this->assertEquals($to->id, $contact->fresh()->structure_version_id);
        $this->assertSame(1, RecordSnapshot::query()->count());
        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_saves_the_previous_state_first_always(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $was = $contact->structure_version_id;

        new MoveRecordToStructureVersion(
            user: $user,
            record: $contact,
            structureVersion: $this->publishedContactVersion($user),
        )->execute();

        $snapshot = RecordSnapshot::query()->firstOrFail();

        $this->assertEquals(SnapshotReasonEnum::TemplateChange, $snapshot->reason);
        $this->assertEquals($was, $snapshot->structure_version_id);
        $this->assertTrue($contact->is($snapshot->snapshotable));
    }

    #[Test]
    public function it_leaves_nothing_behind_when_a_deadlock_rolls_an_attempt_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $pam = $this->contactOf($user);
        $from = $pam->structureVersion;
        $to = $this->publishedContactVersion($user);

        $deadlocked = false;
        Contact::updating(function () use (&$deadlocked): void {
            if ($deadlocked) {
                return;
            }

            $deadlocked = true;

            throw new QueryException('testing', 'update contacts', [], new PDOException('Deadlock found when trying to get lock'));
        });

        $move = new MoveRecordToStructureVersion(user: $user, record: $pam, structureVersion: $to);

        try {
            $move->execute();
            $this->fail('The first attempt was expected to deadlock.');
        } catch (DeadlockException) {
        }

        $this->assertSame($from->id, $pam->fresh()->structure_version_id);
        $this->assertSame(0, RecordSnapshot::query()->count());

        $move->execute();

        $this->assertSame($to->id, $pam->fresh()->structure_version_id);
        $this->assertSame(1, RecordSnapshot::query()->count());
    }

    #[Test]
    public function it_moves_the_counts_from_one_version_to_the_other(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $from = $contact->structureVersion;
        $to = $this->publishedContactVersion($user);

        new MoveRecordToStructureVersion(user: $user, record: $contact, structureVersion: $to)->execute();

        $this->assertSame(0, $from->fresh()->record_count);
        $this->assertSame(1, $to->fresh()->record_count);
    }

    #[Test]
    public function it_sets_aside_the_values_the_new_structure_does_not_carry(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->create(['account_id' => $user->account_id]);
        $value = FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
        ]);

        new MoveRecordToStructureVersion(
            user: $user,
            record: $contact,
            structureVersion: $this->publishedContactVersion($user),
        )->execute();

        $value->refresh();

        $this->assertTrue($value->is_detached);
        $this->assertNotNull($value->detached_at);
        $this->assertEquals('Scranton', $value->value_string);
    }

    #[Test]
    public function it_leaves_alone_the_values_the_new_structure_still_carries(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $to = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($to);
        $value = FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
        ]);

        new MoveRecordToStructureVersion(user: $user, record: $contact, structureVersion: $to)->execute();

        $this->assertFalse($value->fresh()->is_detached);
    }

    #[Test]
    public function it_brings_back_the_values_the_new_structure_carries_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $to = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($to);
        $value = FieldValue::factory()->detached()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
        ]);

        new MoveRecordToStructureVersion(user: $user, record: $contact, structureVersion: $to)->execute();

        $value->refresh();

        $this->assertFalse($value->is_detached);
        $this->assertNull($value->detached_at);
    }

    #[Test]
    public function it_keeps_a_value_whose_field_the_new_version_copied_untouched(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $jim = $this->contactOf($user);
        $birthdayBefore = Field::factory()->recurringDate()->create(['account_id' => $user->account_id, 'key' => 'birthday']);
        $to = $this->publishedContactVersion($user);
        $birthdayAfter = $this->fieldCarriedBy($to);
        $birthdayAfter->sectionVersion->update(['section_id' => $birthdayBefore->sectionVersion->section_id, 'version_number' => 2]);
        $birthdayAfter->update(['key' => 'birthday', 'type' => FieldTypeEnum::RecurringDate]);
        $value = FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $jim->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $jim->id,
            'field_id' => $birthdayBefore->id,
            'value_string' => null,
            'value_date' => '1978-10-01',
        ]);
        $reminder = Reminder::factory()->for($value, 'fieldValue')->create();

        new MoveRecordToStructureVersion(user: $user, record: $jim, structureVersion: $to)->execute();

        $this->assertDatabaseHas('field_values', ['id' => $value->id, 'field_id' => $birthdayAfter->id, 'is_detached' => false]);
        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'field_id' => $birthdayAfter->id, 'is_active' => true]);
    }

    #[Test]
    public function it_stops_the_reminder_on_a_date_the_new_structure_does_not_carry(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $value = FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => Field::factory()->recurringDate()->create(['account_id' => $user->account_id])->id,
            'value_string' => null,
            'value_date' => '1979-03-25',
        ]);
        $reminder = Reminder::factory()->for($value, 'fieldValue')->create();
        $ahead = ReminderOccurrence::factory()->for($reminder)->daysFromNow(3)->create();

        new MoveRecordToStructureVersion(
            user: $user,
            record: $contact,
            structureVersion: $this->publishedContactVersion($user),
        )->execute();

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::FieldRemoved->value,
        ]);
        $this->assertNotNull($ahead->refresh()->cancelled_at);
    }

    #[Test]
    public function it_starts_the_reminder_again_on_a_date_the_new_structure_carries_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $to = $this->publishedContactVersion($user);
        $field = $this->fieldCarriedBy($to);
        $field->update(['type' => FieldTypeEnum::RecurringDate]);
        $value = FieldValue::factory()->detached()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'value_string' => null,
            'value_date' => '1979-03-25',
        ]);
        $reminder = Reminder::factory()->for($value, 'fieldValue')->create([
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::FieldRemoved,
            'deactivated_at' => now(),
        ]);

        new MoveRecordToStructureVersion(user: $user, record: $contact, structureVersion: $to)->execute();

        $this->assertTrue($reminder->refresh()->is_active);
        $this->assertNull($reminder->inactive_reason);
    }

    #[Test]
    public function it_moves_a_record_of_an_invented_kind_too(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $entity = Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'entity_type_id' => $type->id,
            'name' => 'Dunder Mifflin',
        ]);
        $to = $this->publishedEntityVersion($user, $type);

        $moved = new MoveRecordToStructureVersion(user: $user, record: $entity, structureVersion: $to)->execute();

        $this->assertEquals($to->id, $moved->structure_version_id);
    }

    #[Test]
    public function it_refuses_a_template_describing_another_kind_of_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $type = EntityType::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new MoveRecordToStructureVersion(
            user: $user,
            record: $contact,
            structureVersion: $this->publishedEntityVersion($user, $type),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_version_that_is_still_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $draft = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->expectException(InvalidArgumentException::class);

        new MoveRecordToStructureVersion(user: $user, record: $contact, structureVersion: $draft)->execute();
    }

    #[Test]
    public function it_refuses_a_record_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new MoveRecordToStructureVersion(
            user: $user,
            record: Contact::factory()->create(),
            structureVersion: $this->publishedContactVersion($user),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_structure_version_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new MoveRecordToStructureVersion(
            user: $user,
            record: $this->contactOf($user),
            structureVersion: StructureVersion::factory()->published()->create(),
        )->execute();
    }

    #[Test]
    public function it_carries_a_value_to_the_field_of_the_same_key_in_another_section(): void
    {
        Queue::fake();

        $user = $this->userWithSuppliedTemplates();
        $jim = $this->contactOn($user, DefaultTemplateEnum::BasicContact);
        $birthday = $this->valueOf($jim, $this->fieldOf(DefaultTemplateEnum::BasicContact, DefaultFieldEnum::Birthday), ['value_date' => '1978-10-01']);
        $reminder = Reminder::factory()->for($birthday, 'fieldValue')->create();
        [$row, $email] = $this->repeatedValueOf($jim, DefaultTemplateEnum::BasicContact, DefaultFieldEnum::Emails, DefaultFieldEnum::Email, 'jim@dundermifflin.com');

        new MoveRecordToStructureVersion(user: $user, record: $jim, structureVersion: $this->versionOf(DefaultTemplateEnum::Essentials))->execute();

        $this->assertDatabaseHas('field_values', ['id' => $birthday->id, 'field_id' => $this->fieldOf(DefaultTemplateEnum::Essentials, DefaultFieldEnum::Birthday)->id, 'is_detached' => false]);
        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'is_active' => true]);
        $this->assertDatabaseHas('field_value_rows', ['id' => $row->id, 'field_id' => $this->fieldOf(DefaultTemplateEnum::Essentials, DefaultFieldEnum::Emails)->id]);
        $this->assertDatabaseHas('field_values', ['id' => $email->id, 'field_id' => $this->fieldOf(DefaultTemplateEnum::Essentials, DefaultFieldEnum::Email)->id, 'is_detached' => false]);
    }

    #[Test]
    public function it_carries_what_a_repeated_group_holds_only_under_the_group_it_followed_to(): void
    {
        Queue::fake();

        $user = $this->userWithSuppliedTemplates();
        $pam = $this->contactOn($user, DefaultTemplateEnum::FriendsAndFamily);
        [$row, $idea] = $this->repeatedValueOf($pam, DefaultTemplateEnum::FriendsAndFamily, DefaultFieldEnum::GiftIdeas, DefaultFieldEnum::GiftIdea, 'A teapot');

        new MoveRecordToStructureVersion(user: $user, record: $pam, structureVersion: $this->versionOf(DefaultTemplateEnum::FullPortrait))->execute();

        $this->assertDatabaseHas('field_value_rows', ['id' => $row->id, 'field_id' => $this->fieldOf(DefaultTemplateEnum::FullPortrait, DefaultFieldEnum::GiftIdeas)->id]);
        $this->assertDatabaseHas('field_values', ['id' => $idea->id, 'field_id' => $this->fieldOf(DefaultTemplateEnum::FullPortrait, DefaultFieldEnum::GiftIdea)->id, 'is_detached' => false]);
    }

    #[Test]
    public function it_sets_aside_a_value_the_new_version_holds_two_fields_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $dwight = $this->contactOf($user);
        $held = Field::factory()->create(['account_id' => $user->account_id, 'key' => 'nickname', 'type' => FieldTypeEnum::Text]);
        $value = FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $dwight->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $dwight->id,
            'field_id' => $held->id,
            'value_string' => 'Big Tuna',
        ]);
        $to = $this->publishedContactVersion($user);
        $this->fieldCarriedBy($to)->update(['key' => 'nickname', 'type' => FieldTypeEnum::Text]);
        $this->fieldCarriedBy($to)->update(['key' => 'nickname', 'type' => FieldTypeEnum::Text]);

        new MoveRecordToStructureVersion(user: $user, record: $dwight, structureVersion: $to)->execute();

        $this->assertDatabaseHas('field_values', ['id' => $value->id, 'field_id' => $held->id, 'is_detached' => true]);
    }

    #[Test]
    public function it_sets_aside_two_values_wanting_the_same_field_of_the_new_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $dwight = $this->contactOf($user);
        $atWork = Field::factory()->create(['account_id' => $user->account_id, 'key' => 'website', 'type' => FieldTypeEnum::Url]);
        $atHome = Field::factory()->create(['account_id' => $user->account_id, 'key' => 'website', 'type' => FieldTypeEnum::Url]);
        $schruteFarms = $this->valueOf($dwight, $atWork, ['value_string' => 'https://schrutefarms.com']);
        $beetFacts = $this->valueOf($dwight, $atHome, ['value_string' => 'https://beetfacts.com']);
        $to = $this->publishedContactVersion($user);
        $this->fieldCarriedBy($to)->update(['key' => 'website', 'type' => FieldTypeEnum::Url]);

        new MoveRecordToStructureVersion(user: $user, record: $dwight, structureVersion: $to)->execute();

        $this->assertDatabaseHas('field_values', ['id' => $schruteFarms->id, 'field_id' => $atWork->id, 'is_detached' => true]);
        $this->assertDatabaseHas('field_values', ['id' => $beetFacts->id, 'field_id' => $atHome->id, 'is_detached' => true]);
    }

    #[Test]
    public function it_sets_aside_a_value_whose_field_in_the_new_version_already_holds_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $dwight = $this->contactOf($user);
        $held = Field::factory()->create(['account_id' => $user->account_id, 'key' => 'website', 'type' => FieldTypeEnum::Url]);
        $schruteFarms = $this->valueOf($dwight, $held, ['value_string' => 'https://schrutefarms.com']);
        $to = $this->publishedContactVersion($user);
        $carried = $this->fieldCarriedBy($to);
        $carried->update(['key' => 'website', 'type' => FieldTypeEnum::Url]);
        $beetFacts = $this->valueOf($dwight, $carried, ['value_string' => 'https://beetfacts.com']);

        new MoveRecordToStructureVersion(user: $user, record: $dwight, structureVersion: $to)->execute();

        $this->assertDatabaseHas('field_values', ['id' => $schruteFarms->id, 'field_id' => $held->id, 'is_detached' => true]);
        $this->assertDatabaseHas('field_values', ['id' => $beetFacts->id, 'field_id' => $carried->id, 'is_detached' => false]);
    }

    #[Test]
    public function it_carries_what_basic_animal_and_animal_share_and_sets_aside_the_rest(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        new CreateDefaultValueLists(user: $user)->execute();
        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();
        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::Animal)->handle();

        $sprinkles = Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'entity_type_id' => EntityType::query()->where('account_id', $user->account_id)->sole()->id,
            'structure_version_id' => $this->versionOf(DefaultTemplateEnum::BasicAnimal)->id,
            'name' => 'Sprinkles',
        ]);

        $shared = [
            DefaultFieldEnum::Species->value => ['value_string' => 'Cat'],
            DefaultFieldEnum::Breed->value => ['value_string' => 'Persian'],
            DefaultFieldEnum::Birthday->value => ['value_date' => '2001-04-01'],
            DefaultFieldEnum::AdoptionDate->value => ['value_date' => '2002-06-15'],
            DefaultFieldEnum::MicrochipNumber->value => ['value_string' => '250269604123456'],
        ];
        $kept = [];

        foreach ($shared as $key => $attributes) {
            $kept[$key] = $this->valueOnEntity($sprinkles, $this->fieldOf(DefaultTemplateEnum::BasicAnimal, DefaultFieldEnum::from($key)), $attributes);
        }

        $row = FieldValueRow::factory()->create([
            'account_id' => $sprinkles->account_id,
            'valuable_type' => Entity::class,
            'valuable_id' => $sprinkles->id,
            'field_id' => $this->fieldOf(DefaultTemplateEnum::BasicAnimal, DefaultFieldEnum::Caregivers)->id,
        ]);
        $role = $this->valueOnEntity($sprinkles, $this->fieldOf(DefaultTemplateEnum::BasicAnimal, DefaultFieldEnum::CaregiverRole), ['value_string' => 'Owner', 'field_value_row_id' => $row->id]);
        $care = $this->valueOnEntity($sprinkles, $this->fieldOf(DefaultTemplateEnum::BasicAnimal, DefaultFieldEnum::HealthAndCare), ['value_text' => 'Brushed every morning']);

        new MoveRecordToStructureVersion(user: $user, record: $sprinkles, structureVersion: $this->versionOf(DefaultTemplateEnum::Animal))->execute();

        foreach ($kept as $key => $value) {
            $this->assertDatabaseHas('field_values', [
                'id' => $value->id,
                'field_id' => $this->fieldOf(DefaultTemplateEnum::Animal, DefaultFieldEnum::from($key))->id,
                'is_detached' => false,
            ]);
        }

        $this->assertDatabaseHas('field_value_rows', ['id' => $row->id, 'field_id' => $this->fieldOf(DefaultTemplateEnum::Animal, DefaultFieldEnum::Caregivers)->id]);
        $this->assertDatabaseHas('field_values', ['id' => $role->id, 'field_id' => $this->fieldOf(DefaultTemplateEnum::Animal, DefaultFieldEnum::CaregiverRole)->id, 'is_detached' => false]);
        $this->assertDatabaseHas('field_values', ['id' => $care->id, 'is_detached' => true]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function valueOnEntity(Entity $entity, Field $field, array $attributes): FieldValue
    {
        return FieldValue::factory()->create([
            'account_id' => $entity->account_id,
            'vault_id' => $entity->vault_id,
            'valuable_type' => Entity::class,
            'valuable_id' => $entity->id,
            'field_id' => $field->id,
            'value_string' => null,
            ...$attributes,
        ]);
    }

    private function userWithSuppliedTemplates(): User
    {
        $user = User::factory()->create();
        new CreateDefaultValueLists(user: $user)->execute();

        foreach ([DefaultTemplateEnum::BasicContact, DefaultTemplateEnum::Essentials, DefaultTemplateEnum::FriendsAndFamily, DefaultTemplateEnum::FullPortrait] as $supplied) {
            new CreateDefaultTemplate(user: $user, supplied: $supplied)->handle();
        }

        return $user;
    }

    private function versionOf(DefaultTemplateEnum $supplied): StructureVersion
    {
        return Template::query()->where('slug', $supplied->value)->sole()->publishedVersion;
    }

    private function fieldOf(DefaultTemplateEnum $supplied, DefaultFieldEnum $field): Field
    {
        return Field::query()
            ->whereIn('section_version_id', $this->versionOf($supplied)->assembly->pluck('section_version_id'))
            ->where('key', $field->value)
            ->sole();
    }

    private function contactOn(User $user, DefaultTemplateEnum $supplied): Contact
    {
        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'structure_version_id' => $this->versionOf($supplied)->id,
        ]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function valueOf(Contact $contact, Field $field, array $attributes): FieldValue
    {
        return FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'value_string' => null,
            ...$attributes,
        ]);
    }

    /** @return array{FieldValueRow, FieldValue} */
    private function repeatedValueOf(Contact $contact, DefaultTemplateEnum $supplied, DefaultFieldEnum $group, DefaultFieldEnum $inside, string $text): array
    {
        $row = FieldValueRow::factory()->create([
            'account_id' => $contact->account_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $this->fieldOf($supplied, $group)->id,
        ]);

        $value = $this->valueOf($contact, $this->fieldOf($supplied, $inside), ['value_string' => $text, 'field_value_row_id' => $row->id]);

        return [$row, $value];
    }

    private function contactOf(User $user): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $this->publishedContactVersion($user, 1),
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
        ]);
    }

    private function publishedContactVersion(User $user, int $recordCount = 0): StructureVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        return StructureVersion::factory()->published()->create([
            'structure_id' => $template->structure->id,
            'record_count' => $recordCount,
        ]);
    }

    private function publishedEntityVersion(User $user, EntityType $entityType): StructureVersion
    {
        $template = Template::factory()->forEntity($entityType)->create(['account_id' => $user->account_id]);

        return StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
    }

    private function fieldCarriedBy(StructureVersion $version): Field
    {
        $version->status = VersionStatusEnum::Draft;
        $version->save();

        $sectionVersion = SectionVersion::factory()->create(['account_id' => $version->account_id]);
        $field = Field::factory()->create([
            'account_id' => $version->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        StructureVersionSection::factory()->create([
            'account_id' => $version->account_id,
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $version->pages->first()->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $sectionVersion->status = VersionStatusEnum::Published;
        $sectionVersion->save();

        $version->status = VersionStatusEnum::Published;
        $version->save();

        return $field;
    }

    #[Test]
    public function it_sends_the_moved_record_to_search_again(): void
    {
        Queue::fake();
        $engine = $this->useRecordingSearchEngine();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $to = $this->publishedContactVersion($user);
        $engine->forget();

        new MoveRecordToStructureVersion(user: $user, record: $contact, structureVersion: $to)->execute();

        $this->assertTrue($engine->wasSent($contact));
    }
}
