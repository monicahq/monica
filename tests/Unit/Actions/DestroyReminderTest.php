<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyReminder;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_the_reminder_away_and_keeps_it_restorable(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);

        new DestroyReminder(user: $user, reminder: $reminder)->execute();

        $this->assertSoftDeleted($reminder);
        $this->assertEquals(1, Reminder::query()->withTrashed()->count());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ReminderRemoval
                && $job->account->id === $user->account_id
                && $job->vault->id === $reminder->vault_id
                && $job->parameters === ['label' => 'Birthday', 'name' => $reminder->remindable->name],
        );
    }

    #[Test]
    public function it_leaves_the_date_it_read_exactly_where_it_was(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);
        $value = $reminder->fieldValue;

        new DestroyReminder(user: $user, reminder: $reminder)->execute();

        $this->assertDatabaseHas('field_values', [
            'id' => $value->id,
            'value_date' => '1984-02-03 00:00:00',
        ]);
    }

    #[Test]
    public function it_leaves_the_date_free_to_carry_a_new_reminder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->ruleOf($user);

        new DestroyReminder(user: $user, reminder: $reminder)->execute();

        $this->assertFalse($reminder->fieldValue->reminder()->exists());
    }

    #[Test]
    public function it_refuses_a_reminder_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $somebodyElse = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyReminder(user: $user, reminder: $this->ruleOf($somebodyElse))->execute();
    }

    private function ruleOf(User $user): Reminder
    {
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->recurringDate()->create(['account_id' => $user->account_id]);
        $value = FieldValue::factory()->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field->id,
            'value_string' => null,
            'value_date' => '1984-02-03',
        ]);

        return Reminder::factory()->for($value, 'fieldValue')->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'remindable_type' => Contact::class,
            'remindable_id' => $contact->id,
            'field_id' => $field->id,
        ]);
    }
}
