<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyFieldValue;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyFieldValueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_empties_one_field_of_one_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $value = $this->valueOn($user, $contact);

        new DestroyFieldValue(user: $user, fieldValue: $value)->execute();

        $this->assertModelMissing($value);
    }

    #[Test]
    public function it_lets_go_of_the_file_the_value_held(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $file = File::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'reference_count' => 1,
        ]);
        $value = $this->valueOn($user, $contact, ['value_string' => null, 'value_file_id' => $file->id]);

        new DestroyFieldValue(user: $user, fieldValue: $value)->execute();

        $file->refresh();

        $this->assertSame(0, $file->reference_count);
        $this->assertNotNull($file->orphaned_at);
    }

    #[Test]
    public function it_gives_back_the_count_the_value_was_holding_on_a_chosen_item(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $item = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'usage_count' => 1,
        ]);
        $value = $this->valueOn($user, $contact, [
            'field_id' => Field::factory()->choice($list)->create(['account_id' => $user->account_id])->id,
            'value_string' => null,
            'value_list_item_id' => $item->id,
        ]);

        new DestroyFieldValue(user: $user, fieldValue: $value)->execute();

        $this->assertSame(0, $item->fresh()->usage_count);
    }

    #[Test]
    public function it_refuses_a_value_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyFieldValue(user: $user, fieldValue: FieldValue::factory()->create())->execute();
    }

    #[Test]
    public function it_logs_the_clearing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $value = $this->valueOn($user, $contact, [
            'field_id' => Field::factory()->create(['account_id' => $user->account_id, 'label' => 'City'])->id,
        ]);

        new DestroyFieldValue(user: $user, fieldValue: $value)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::FieldValueCleared
                && $job->parameters === ['label' => 'City', 'name' => 'Pam Beesly'],
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
    private function valueOn(User $user, Contact $contact, array $attributes = []): FieldValue
    {
        return FieldValue::factory()->create(array_merge([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
        ], $attributes));
    }
}
