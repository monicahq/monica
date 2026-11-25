<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateRecordSnapshot;
use App\Enums\FieldTypeEnum;
use App\Enums\SnapshotReasonEnum;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\RecordSnapshot;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateRecordSnapshotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_captures_the_state_of_a_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();

        $this->assertInstanceOf(RecordSnapshot::class, $snapshot);
        $this->assertDatabaseHas('record_snapshots', [
            'id' => $snapshot->id,
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'snapshotable_type' => Contact::class,
            'snapshotable_id' => $contact->id,
            'structure_version_id' => $contact->structure_version_id,
            'reason' => SnapshotReasonEnum::Manual->value,
            'created_by_user_id' => $user->id,
            'restored_at' => null,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::RecordSnapshotTaken
                && $job->isAbout($snapshot)
                && $job->parameters === ['name' => 'Pam Beesly', 'reason' => 'manual'],
        );
    }

    #[Test]
    public function it_captures_without_writing_to_the_log_when_told_not_to(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact, writesToLog: false)->execute();

        $this->assertModelExists($snapshot);
        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_writes_down_every_part_of_the_name_a_contact_reads_as(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();

        $this->assertEquals('Pam', $snapshot->payload['record']['first_name']);
        $this->assertEquals('Beesly', $snapshot->payload['record']['last_name']);
        $this->assertEquals(Contact::class, $snapshot->payload['record']['type']);
    }

    #[Test]
    public function it_writes_down_the_name_and_the_kind_of_an_entity(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $entity = Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'name' => 'Dunder Mifflin',
        ]);

        $snapshot = new CreateRecordSnapshot(user: $user, record: $entity)->execute();

        $this->assertEquals('Dunder Mifflin', $snapshot->payload['record']['name']);
        $this->assertEquals($entity->entity_type_id, $snapshot->payload['record']['entity_type_id']);
    }

    #[Test]
    public function it_writes_down_the_structure_as_it_read_at_the_time(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOnThePageOf($contact, ['key' => 'city', 'label' => 'City']);

        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();

        $page = $snapshot->payload['structure'][0];
        $section = $page['sections'][0];

        $this->assertEquals($contact->structureVersion->pages->first()->key, $page['key']);
        $this->assertEquals('city', $section['fields'][0]['key']);
        $this->assertEquals('City', $section['fields'][0]['label']);
        $this->assertEquals($field->id, $section['fields'][0]['id']);
    }

    #[Test]
    public function it_writes_down_everything_filled_in(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = $this->fieldOnThePageOf($contact, ['key' => 'city']);
        FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'field_key' => 'city',
            'value_string' => 'Scranton',
        ]);

        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();

        $this->assertCount(1, $snapshot->payload['values']);
        $this->assertEquals('Scranton', $snapshot->payload['values'][0]['value_string']);
        $this->assertEquals('city', $snapshot->payload['values'][0]['field_key']);
    }

    #[Test]
    public function it_writes_down_the_values_that_had_been_set_aside_too(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        FieldValue::factory()->detached()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'value_string' => 'Utica',
        ]);

        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();

        $this->assertCount(1, $snapshot->payload['values']);
        $this->assertTrue($snapshot->payload['values'][0]['is_detached']);
    }

    #[Test]
    public function it_writes_down_the_repetitions_so_what_went_together_goes_back_together(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $repeater = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $row = FieldValueRow::factory()->create([
            'account_id' => $user->account_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $repeater->id,
            'position' => 2,
        ]);

        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();

        $this->assertCount(1, $snapshot->payload['rows']);
        $this->assertEquals($row->id, $snapshot->payload['rows'][0]['id']);
        $this->assertEquals(2, $snapshot->payload['rows'][0]['position']);
    }

    #[Test]
    public function it_names_the_files_the_snapshot_points_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $avatar = File::factory()->create(['account_id' => $user->account_id, 'vault_id' => $contact->vault_id]);
        $attached = File::factory()->create(['account_id' => $user->account_id, 'vault_id' => $contact->vault_id]);
        $contact->avatar_file_id = $avatar->id;
        $contact->save();
        FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $this->fieldOnThePageOf($contact, ['type' => FieldTypeEnum::File])->id,
            'value_string' => null,
            'value_file_id' => $attached->id,
        ]);

        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();

        $this->assertEqualsCanonicalizing([$avatar->id, $attached->id], $snapshot->file_ids);
    }

    #[Test]
    public function it_takes_a_snapshot_for_a_reason_the_caller_names(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $snapshot = new CreateRecordSnapshot(
            user: $user,
            record: $contact,
            reason: SnapshotReasonEnum::Import,
        )->execute();

        $this->assertEquals(SnapshotReasonEnum::Import, $snapshot->reason);
    }

    #[Test]
    public function it_refuses_a_record_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateRecordSnapshot(user: $user, record: Contact::factory()->create())->execute();
    }

    private function contactOf(User $user): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => StructureVersion::factory()->published()->create(['account_id' => $user->account_id]),
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
        ]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function fieldOnThePageOf(Contact $contact, array $attributes = []): Field
    {
        $version = $contact->structureVersion;
        $version->status = VersionStatusEnum::Draft;
        $version->save();

        $sectionVersion = SectionVersion::factory()->create(['account_id' => $version->account_id]);
        $field = Field::factory()->create(array_merge([
            'account_id' => $version->account_id,
            'section_version_id' => $sectionVersion->id,
        ], $attributes));

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

        $contact->unsetRelation('structureVersion');

        return $field;
    }

    #[Test]
    public function it_writes_down_every_way_the_name_is_written_and_the_one_shown(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $kana = ContactNameRepresentation::factory()->transliteration()->create(['contact_id' => $contact->id, 'script_code' => 'Kana', 'formatted_name' => 'パム・ビーズリー']);

        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact->refresh())->execute();

        $this->assertSame($contact->displayNameRepresentation->uuid, $snapshot->payload['record']['display_name_representation_uuid']);
        $this->assertCount(2, $snapshot->payload['record']['name_representations']);
        $this->assertSame($kana->uuid, $snapshot->payload['record']['name_representations'][1]['id']);
        $this->assertSame('パム・ビーズリー', $snapshot->payload['record']['name_representations'][1]['formatted_name']);
    }
}
