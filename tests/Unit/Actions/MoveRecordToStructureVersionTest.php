<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\MoveRecordToStructureVersion;
use App\Enums\FieldTypeEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\SnapshotReasonEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\RecordSnapshot;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
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
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::RecordMovedToTemplateVersion
                && $job->isAbout($contact)
                && $job->parameters === ['name' => 'Pam Beesly', 'version' => $to->version_number],
        );
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
