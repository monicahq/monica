<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateFieldValueRowPosition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateFieldValueRowPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_repetition_above_the_others(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $first = $this->rowOn($user, $contact, $field, 1);
        $second = $this->rowOn($user, $contact, $field, 2);
        $third = $this->rowOn($user, $contact, $field, 3);

        new UpdateFieldValueRowPosition(user: $user, row: $third, position: 1)->execute();

        $this->assertSame(1, $third->fresh()->position);
        $this->assertSame(2, $first->fresh()->position);
        $this->assertSame(3, $second->fresh()->position);
    }

    #[Test]
    public function it_puts_a_repetition_last_when_the_position_is_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $first = $this->rowOn($user, $contact, $field, 1);
        $second = $this->rowOn($user, $contact, $field, 2);

        new UpdateFieldValueRowPosition(user: $user, row: $first, position: 99)->execute();

        $this->assertSame(2, $first->fresh()->position);
        $this->assertSame(1, $second->fresh()->position);
    }

    #[Test]
    public function it_never_changes_which_repetition_a_value_belongs_to(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->repeater()->create(['account_id' => $user->account_id]);
        $first = $this->rowOn($user, $contact, $field, 1);
        $second = $this->rowOn($user, $contact, $field, 2);
        $value = FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_value_row_id' => $first->id,
            'value_string' => '570-555-0123',
        ]);

        new UpdateFieldValueRowPosition(user: $user, row: $second, position: 1)->execute();

        $value->refresh();

        $this->assertEquals($first->id, $value->field_value_row_id);
        $this->assertEquals('570-555-0123', $value->value_string);
    }

    #[Test]
    public function it_refuses_a_repetition_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateFieldValueRowPosition(
            user: $user,
            row: FieldValueRow::factory()->create(),
            position: 1,
        )->execute();
    }

    #[Test]
    public function it_logs_the_move(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->repeater()->create([
            'account_id' => $user->account_id,
            'label' => 'Phone numbers',
        ]);
        $row = $this->rowOn($user, $contact, $field, 1);

        new UpdateFieldValueRowPosition(user: $user, row: $row, position: 1)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::FieldValueRowPositionUpdate
                && $job->loggable->is($row)
                && $job->parameters === ['label' => 'Phone numbers', 'name' => 'Pam Beesly'],
        );
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

    private function rowOn(User $user, Contact $contact, Field $field, int $position): FieldValueRow
    {
        return FieldValueRow::factory()->create([
            'account_id' => $user->account_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'position' => $position,
        ]);
    }
}
