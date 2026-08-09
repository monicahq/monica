<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateContactName;
use App\Enums\NameOrderEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ContactNeedsAName;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateContactNameTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_how_a_contact_is_called(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
        ]);

        $contact = new UpdateContactName(
            user: $user,
            contact: $contact,
            prefix: 'Mrs',
            firstName: 'Pam',
            lastName: 'Halpert',
            maidenName: 'Beesly',
            nickname: 'Pammy',
            nameOrder: NameOrderEnum::FamilyFirst,
        )->execute();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'prefix' => 'Mrs',
            'first_name' => 'Pam',
            'last_name' => 'Halpert',
            'maiden_name' => 'Beesly',
            'nickname' => 'Pammy',
            'name_order' => NameOrderEnum::FamilyFirst->value,
            'sort_name' => 'halpert pam',
        ]);
    }

    #[Test]
    public function it_empties_the_parts_it_is_not_given_any_more(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'first_name' => 'Michael',
            'middle_name' => 'Gary',
            'last_name' => 'Scott',
        ]);

        $contact = new UpdateContactName(
            user: $user,
            contact: $contact,
            firstName: 'Michael',
            lastName: 'Scott',
        )->execute();

        $this->assertNull($contact->middle_name);
    }

    #[Test]
    public function it_renames_the_contact_representing_the_user(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->me()->create(['account_id' => $user->account_id]);

        $contact = new UpdateContactName(
            user: $user,
            contact: $contact,
            firstName: 'Michael',
            lastName: 'Scarn',
        )->execute();

        $this->assertEquals('Michael Scarn', $contact->name);
        $this->assertTrue($contact->is_me);
    }

    #[Test]
    public function it_refuses_to_leave_a_contact_with_no_name_at_all(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ContactNeedsAName::class);

        new UpdateContactName(user: $user, contact: $contact, prefix: 'Dr')->execute();
    }

    #[Test]
    public function it_strips_the_markup_out_of_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);

        $contact = new UpdateContactName(
            user: $user,
            contact: $contact,
            firstName: '<script>alert("Dwight")</script>Dwight',
            lastName: '<b>Schrute</b>',
        )->execute();

        $this->assertEquals('Dwight', $contact->first_name);
        $this->assertEquals('Schrute', $contact->last_name);
    }

    #[Test]
    public function it_refuses_a_contact_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateContactName(user: $user, contact: $contact, firstName: 'Dwight')->execute();
    }

    #[Test]
    public function it_logs_what_changed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        new UpdateContactName(
            user: $user,
            contact: $contact,
            firstName: 'Michael',
            lastName: 'Scarn',
        )->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactNameUpdate
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->loggable->is($contact)
                && $job->parameters === ['name' => 'Michael Scarn']
                && $job->changes === ['last_name' => ['before' => 'Scott', 'after' => 'Scarn']],
        );
    }
}
