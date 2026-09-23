<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateFieldValueRow;
use App\Enums\UserActionEnum;
use App\Exceptions\TooManyRepetitions;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValueRow;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateFieldValueRowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_repetition_to_a_repeater(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->repeater()->create([
            'account_id' => $user->account_id,
            'label' => 'Phone numbers',
        ]);

        $row = new CreateFieldValueRow(user: $user, valuable: $contact, field: $field)->execute();

        $this->assertInstanceOf(FieldValueRow::class, $row);
        $this->assertDatabaseHas('field_value_rows', [
            'id' => $row->id,
            'account_id' => $user->account_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'position' => 1,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::FieldValueRowCreation
                && $job->isAbout($row)
                && $job->parameters === ['label' => 'Phone numbers', 'name' => 'Pam Beesly'],
        );
    }

    #[Test]
    public function it_adds_the_repetition_at_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->repeater()->create(['account_id' => $user->account_id]);

        new CreateFieldValueRow(user: $user, valuable: $contact, field: $field)->execute();
        $second = new CreateFieldValueRow(user: $user, valuable: $contact, field: $field)->execute();

        $this->assertSame(2, $second->position);
    }

    #[Test]
    public function it_adds_a_repetition_to_a_record_of_an_invented_kind_too(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $entity = Entity::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'name' => 'Dunder Mifflin',
        ]);
        $field = Field::factory()->repeater()->create(['account_id' => $user->account_id]);

        $row = new CreateFieldValueRow(user: $user, valuable: $entity, field: $field)->execute();

        $this->assertEquals(Entity::class, $row->valuable_type);
        $this->assertTrue($entity->is($row->valuable));
    }

    #[Test]
    public function it_refuses_a_field_that_is_not_a_repeater(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new CreateFieldValueRow(user: $user, valuable: $contact, field: $field)->execute();
    }

    #[Test]
    public function it_refuses_one_repetition_more_than_the_field_allows(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);
        $field = Field::factory()->repeater()->create([
            'account_id' => $user->account_id,
            'repeat_max' => 2,
        ]);

        new CreateFieldValueRow(user: $user, valuable: $contact, field: $field)->execute();
        new CreateFieldValueRow(user: $user, valuable: $contact, field: $field)->execute();

        $this->expectException(TooManyRepetitions::class);

        new CreateFieldValueRow(user: $user, valuable: $contact, field: $field)->execute();
    }

    #[Test]
    public function it_refuses_a_record_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $field = Field::factory()->repeater()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new CreateFieldValueRow(user: $user, valuable: Contact::factory()->create(), field: $field)->execute();
    }

    #[Test]
    public function it_refuses_a_field_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = $this->contactOf($user);

        $this->expectException(ModelNotFoundException::class);

        new CreateFieldValueRow(
            user: $user,
            valuable: $contact,
            field: Field::factory()->repeater()->create(),
        )->execute();
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
}
