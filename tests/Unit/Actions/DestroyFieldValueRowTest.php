<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyFieldValueRow;
use App\Enums\UserActionEnum;
use App\Exceptions\NotEnoughRepetitions;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyFieldValueRowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_a_repetition_and_what_was_filled_in_on_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->repeater()->create([
            'account_id' => $user->account_id,
            'label' => 'Phone numbers',
        ]);
        $row = $this->rowOn($user, $contact, $field);
        $value = FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_value_row_id' => $row->id,
        ]);

        new DestroyFieldValueRow(user: $user, row: $row)->execute();

        $this->assertModelMissing($row);
        $this->assertModelMissing($value);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::FieldValueRowDeletion
                && $job->parameters === ['label' => 'Phone numbers', 'name' => 'Pam Beesly'],
        );
    }

    #[Test]
    public function it_lets_go_of_the_files_the_repetition_held(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $row = $this->rowOn($user, $contact);
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
            'field_value_row_id' => $row->id,
            'value_string' => null,
            'value_file_id' => $file->id,
        ]);

        new DestroyFieldValueRow(user: $user, row: $row)->execute();

        $file->refresh();

        $this->assertSame(0, $file->reference_count);
        $this->assertNotNull($file->orphaned_at);
        $this->assertDatabaseHas('files', ['id' => $file->id, 'deleted_at' => null]);
    }

    #[Test]
    public function it_numbers_the_repetitions_that_are_left_from_one_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $first = $this->rowOn($user, $contact, $field, 1);
        $second = $this->rowOn($user, $contact, $field, 2);
        $third = $this->rowOn($user, $contact, $field, 3);

        new DestroyFieldValueRow(user: $user, row: $second)->execute();

        $this->assertSame(1, $first->fresh()->position);
        $this->assertSame(2, $third->fresh()->position);
    }

    #[Test]
    public function it_refuses_to_leave_fewer_repetitions_than_the_field_asks_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->repeater()->create([
            'account_id' => $user->account_id,
            'repeat_min' => 1,
        ]);
        $row = $this->rowOn($user, $contact, $field);

        $this->expectException(NotEnoughRepetitions::class);

        new DestroyFieldValueRow(user: $user, row: $row)->execute();
    }

    #[Test]
    public function it_refuses_a_repetition_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyFieldValueRow(user: $user, row: FieldValueRow::factory()->create())->execute();
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

    private function rowOn(User $user, Contact $contact, ?Field $field = null, int $position = 1): FieldValueRow
    {
        return FieldValueRow::factory()->create([
            'account_id' => $user->account_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id ?? Field::factory()->repeater()->create(['account_id' => $user->account_id])->id,
            'position' => $position,
        ]);
    }
}
