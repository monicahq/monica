<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DetachFieldValues;
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

class DetachFieldValuesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sets_aside_everything_written_into_a_field_without_destroying_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->create(['account_id' => $user->account_id]);
        $value = $this->valueOn($user, $contact, $field);

        $count = new DetachFieldValues(user: $user, field: $field)->execute();

        $value->refresh();

        $this->assertSame(1, $count);
        $this->assertTrue($value->is_detached);
        $this->assertNotNull($value->detached_at);
        $this->assertEquals('Scranton', $value->value_string);
    }

    #[Test]
    public function it_takes_the_values_it_sets_aside_off_the_page(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->create(['account_id' => $user->account_id]);
        $this->valueOn($user, $contact, $field);

        new DetachFieldValues(user: $user, field: $field)->execute();

        $this->assertCount(0, $contact->fieldValues()->attached()->get());
    }

    #[Test]
    public function it_leaves_the_values_of_every_other_field_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->create(['account_id' => $user->account_id]);
        $another = Field::factory()->create(['account_id' => $user->account_id]);
        $this->valueOn($user, $contact, $field);
        $untouched = $this->valueOn($user, $contact, $another);

        new DetachFieldValues(user: $user, field: $field)->execute();

        $this->assertFalse($untouched->fresh()->is_detached);
    }

    #[Test]
    public function it_lets_go_of_the_files_the_values_held(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->create(['account_id' => $user->account_id]);
        $file = File::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'reference_count' => 1,
        ]);
        $this->valueOn($user, $contact, $field, ['value_string' => null, 'value_file_id' => $file->id]);

        new DetachFieldValues(user: $user, field: $field)->execute();

        $file->refresh();

        $this->assertSame(0, $file->reference_count);
        $this->assertNotNull($file->orphaned_at);
        $this->assertDatabaseHas('files', ['id' => $file->id, 'deleted_at' => null]);
    }

    #[Test]
    public function it_refuses_a_field_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DetachFieldValues(user: $user, field: Field::factory()->create())->execute();
    }

    #[Test]
    public function it_logs_how_many_values_were_set_aside(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->create(['account_id' => $user->account_id, 'label' => 'City']);
        $this->valueOn($user, $contact, $field);

        new DetachFieldValues(user: $user, field: $field)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::FieldValuesDetached
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function valueOn(User $user, Contact $contact, Field $field, array $attributes = []): FieldValue
    {
        return FieldValue::factory()->create(array_merge([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'value_string' => 'Scranton',
        ], $attributes));
    }
}
