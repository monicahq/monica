<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateRecordSnapshot;
use App\Actions\RestoreRecordSnapshot;
use App\Actions\SetFieldValue;
use App\Enums\SnapshotReasonEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\SnapshotCannotBeRestored;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\RecordSnapshot;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreRecordSnapshotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_the_values_back_the_way_they_were(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->create(['account_id' => $user->account_id]);

        new SetFieldValue(user: $user, valuable: $contact, field: $field, value: 'Scranton')->execute();
        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();
        new SetFieldValue(user: $user, valuable: $contact, field: $field, value: 'Utica')->execute();

        new RestoreRecordSnapshot(user: $user, snapshot: $snapshot)->execute();

        $this->assertSame(1, FieldValue::query()->count());
        $this->assertEquals('Scranton', FieldValue::query()->first()->value_string);
    }

    #[Test]
    public function it_puts_the_name_of_the_record_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();

        $contact->last_name = 'Halpert';
        $contact->save();

        new RestoreRecordSnapshot(user: $user, snapshot: $snapshot)->execute();

        $this->assertEquals('Beesly', $contact->fresh()->last_name);
    }

    #[Test]
    public function it_takes_a_snapshot_of_the_present_first_so_a_restore_can_be_undone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();

        new RestoreRecordSnapshot(user: $user, snapshot: $snapshot)->execute();

        $this->assertSame(1, RecordSnapshot::query()->where('reason', SnapshotReasonEnum::Restore)->count());
    }

    #[Test]
    public function it_writes_down_when_the_snapshot_was_used(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();

        new RestoreRecordSnapshot(user: $user, snapshot: $snapshot)->execute();

        $this->assertNotNull($snapshot->fresh()->restored_at);
    }

    #[Test]
    public function it_puts_the_repetitions_back_with_the_values_in_the_right_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $repeater = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $child = Field::factory()->create([
            'account_id' => $user->account_id,
            'parent_field_id' => $repeater->id,
        ]);
        $first = $this->rowOn($user, $contact, $repeater, 1);
        $second = $this->rowOn($user, $contact, $repeater, 2);

        new SetFieldValue(user: $user, valuable: $contact, field: $child, row: $first, value: '570-555-0123')->execute();
        new SetFieldValue(user: $user, valuable: $contact, field: $child, row: $second, value: '570-555-9876')->execute();

        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();

        FieldValue::query()->delete();
        FieldValueRow::query()->delete();

        new RestoreRecordSnapshot(user: $user, snapshot: $snapshot)->execute();

        $rows = FieldValueRow::query()->orderBy('position')->get();
        $values = FieldValue::query()->get();

        $this->assertCount(2, $rows);
        $this->assertCount(2, $values);
        $this->assertEquals(
            '570-555-0123',
            $values->firstWhere('field_value_row_id', $rows->first()->id)->value_string,
        );
        $this->assertEquals(
            '570-555-9876',
            $values->firstWhere('field_value_row_id', $rows->last()->id)->value_string,
        );
    }

    #[Test]
    public function it_points_at_the_files_the_snapshot_held_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $file = File::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'reference_count' => 1,
        ]);
        FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'value_string' => null,
            'value_file_id' => $file->id,
        ]);

        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();

        new RestoreRecordSnapshot(user: $user, snapshot: $snapshot)->execute();

        $this->assertSame(1, $file->fresh()->reference_count);
    }

    #[Test]
    public function it_puts_the_record_back_on_the_structure_it_was_rendering_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $was = $contact->templateVersion;
        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();

        $now = TemplateVersion::factory()->published()->create([
            'account_id' => $user->account_id,
            'record_count' => 1,
        ]);
        $contact->template_version_id = $now->id;
        $contact->save();
        $was->decrement('record_count');

        new RestoreRecordSnapshot(user: $user, snapshot: $snapshot)->execute();

        $this->assertEquals($was->id, $contact->fresh()->template_version_id);
        $this->assertSame(1, $was->fresh()->record_count);
        $this->assertSame(0, $now->fresh()->record_count);
    }

    #[Test]
    public function it_refuses_when_a_field_the_snapshot_wrote_into_is_gone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();

        $payload = $snapshot->payload;
        $payload['values'] = [['field_id' => 9999, 'field_key' => 'gone']];
        RecordSnapshot::query()->whereKey($snapshot->id)->update(['payload' => json_encode($payload)]);

        $this->expectException(SnapshotCannotBeRestored::class);

        new RestoreRecordSnapshot(user: $user, snapshot: $snapshot->fresh())->execute();
    }

    #[Test]
    public function it_refuses_a_snapshot_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new RestoreRecordSnapshot(user: $user, snapshot: RecordSnapshot::factory()->create())->execute();
    }

    #[Test]
    public function it_logs_the_restoration(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $snapshot = new CreateRecordSnapshot(user: $user, record: $contact)->execute();

        new RestoreRecordSnapshot(user: $user, snapshot: $snapshot)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::RecordSnapshotRestored
                && $job->loggable->is($snapshot)
                && $job->parameters === ['name' => 'Pam Beesly'],
        );
    }

    private function contactOf(User $user): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'template_version_id' => TemplateVersion::factory()->published()->create([
                'account_id' => $user->account_id,
                'record_count' => 1,
            ]),
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
        ]);
    }

    private function rowOn(User $user, Contact $contact, Field $repeater, int $position): FieldValueRow
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
