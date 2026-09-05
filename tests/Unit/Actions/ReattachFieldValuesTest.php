<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DetachFieldValues;
use App\Actions\ReattachFieldValues;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReattachFieldValuesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_back_on_the_page_what_the_field_held_before_it_left(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->create(['account_id' => $user->account_id]);
        $value = $this->detachedValueOn($user, $contact, $field);

        $count = new ReattachFieldValues(user: $user, field: $field)->execute();

        $value->refresh();

        $this->assertSame(1, $count);
        $this->assertFalse($value->is_detached);
        $this->assertNull($value->detached_at);
        $this->assertCount(1, $contact->fieldValues()->attached()->get());
    }

    #[Test]
    public function it_leaves_the_values_of_every_other_field_set_aside(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->create(['account_id' => $user->account_id]);
        $another = Field::factory()->create(['account_id' => $user->account_id]);
        $this->detachedValueOn($user, $contact, $field);
        $untouched = $this->detachedValueOn($user, $contact, $another);

        new ReattachFieldValues(user: $user, field: $field)->execute();

        $this->assertTrue($untouched->fresh()->is_detached);
    }

    #[Test]
    public function it_points_at_the_files_the_values_held_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->create(['account_id' => $user->account_id]);
        $file = File::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'reference_count' => 0,
            'orphaned_at' => now(),
        ]);
        $this->detachedValueOn($user, $contact, $field, ['value_string' => null, 'value_file_id' => $file->id]);

        new ReattachFieldValues(user: $user, field: $field)->execute();

        $file->refresh();

        $this->assertSame(1, $file->reference_count);
        $this->assertNull($file->orphaned_at);
    }

    #[Test]
    public function it_refuses_a_field_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new ReattachFieldValues(user: $user, field: Field::factory()->create())->execute();
    }

    #[Test]
    public function it_logs_how_many_values_came_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->create(['account_id' => $user->account_id, 'label' => 'City']);
        $this->detachedValueOn($user, $contact, $field);

        new ReattachFieldValues(user: $user, field: $field)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::FieldValuesReattached
                && $job->parameters === ['label' => 'City', 'count' => 1],
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

    /** @param  array<string, mixed>  $attributes */
    private function detachedValueOn(User $user, Contact $contact, Field $field, array $attributes = []): FieldValue
    {
        return FieldValue::factory()->detached()->create(array_merge([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'value_string' => 'Scranton',
        ], $attributes));
    }

    #[Test]
    public function it_brings_back_the_reminders_the_field_leaving_silenced(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->reminderAbout($user);

        new DetachFieldValues(user: $user, field: $reminder->field)->execute();
        new ReattachFieldValues(user: $user, field: $reminder->field)->execute();

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'is_active' => true,
            'inactive_reason' => null,
        ]);
    }
}
