<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateContactName;
use App\Enums\NameOrderEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
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

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactNameUpdate
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->isAbout($contact)
                && $job->parameters === ['name' => 'Halpert Pam']
                && $job->changes === [
                    'prefix' => ['before' => null, 'after' => 'Mrs'],
                    'last_name' => ['before' => 'Beesly', 'after' => 'Halpert'],
                    'maiden_name' => ['before' => null, 'after' => 'Beesly'],
                    'nickname' => ['before' => null, 'after' => 'Pammy'],
                ],
        );
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
    public function it_lets_a_contact_be_left_with_no_name_at_all(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);

        new UpdateContactName(user: $user, contact: $contact, prefix: 'Dr')->execute();

        $this->assertTrue($contact->refresh()->isNameless());
        $this->assertEquals(__('Unnamed contact'), $contact->name);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'first_name' => null, 'last_name' => null]);
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
    public function it_changes_the_displayed_name_and_leaves_the_other_ways_of_writing_it_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);
        $other = ContactNameRepresentation::factory()->transliteration()->create(['contact_id' => $contact->id, 'formatted_name' => 'Pamela Beesly']);
        $displayedId = $contact->display_name_representation_id;

        new UpdateContactName(user: $user, contact: $contact, firstName: 'Pam', lastName: 'Halpert')->execute();

        $this->assertDatabaseHas('contact_name_representations', ['id' => $displayedId, 'family_name' => 'Halpert']);
        $this->assertDatabaseHas('contact_name_representations', ['id' => $other->id, 'formatted_name' => 'Pamela Beesly']);
        $this->assertSame($displayedId, $contact->refresh()->display_name_representation_id);
    }

    #[Test]
    public function it_gives_a_contact_with_no_name_its_first_way_of_writing_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => null, 'last_name' => null]);

        new UpdateContactName(user: $user, contact: $contact, firstName: 'Kevin', lastName: 'Malone')->execute();

        $this->assertSame('Kevin', $contact->refresh()->displayNameRepresentation->given_name);
    }

    #[Test]
    public function it_leaves_a_contact_nameless_when_every_part_is_emptied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Creed', 'last_name' => 'Bratton']);

        new UpdateContactName(user: $user, contact: $contact)->execute();

        $contact->refresh();
        $this->assertNull($contact->display_name_representation_id);
        $this->assertSame(0, $contact->nameRepresentations()->count());
        $this->assertTrue($contact->isNameless());
    }

    #[Test]
    public function it_shows_the_next_way_of_writing_the_name_when_the_displayed_one_is_emptied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Kelly', 'last_name' => 'Kapoor']);
        $other = ContactNameRepresentation::factory()->create(['contact_id' => $contact->id, 'formatted_name' => 'केली कपूर', 'given_name' => null, 'family_name' => null]);

        new UpdateContactName(user: $user, contact: $contact)->execute();

        $this->assertSame($other->id, $contact->refresh()->display_name_representation_id);
        $this->assertSame('केली कपूर', $contact->name);
    }

    #[Test]
    public function it_keeps_a_name_written_only_in_full_when_only_the_nickname_changes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => null, 'last_name' => null]);
        $displayed = ContactNameRepresentation::factory()->create(['contact_id' => $contact->id, 'formatted_name' => 'Jim Halpert', 'given_name' => null, 'family_name' => null]);
        ContactNameRepresentation::factory()->transliteration()->create(['contact_id' => $contact->id, 'formatted_name' => 'Big Tuna']);
        $contact->display_name_representation_id = $displayed->id;
        $contact->save();

        new UpdateContactName(user: $user, contact: $contact, nickname: 'Jimbo')->execute();

        $contact->refresh();
        $this->assertSame($displayed->id, $contact->display_name_representation_id);
        $this->assertSame('Jim Halpert', $contact->name);
        $this->assertSame('Jimbo', $contact->nickname);
        $this->assertSame(2, $contact->nameRepresentations()->count());
    }

    #[Test]
    public function it_keeps_the_full_name_when_the_parts_it_is_given_are_the_ones_it_holds(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => null, 'last_name' => null]);
        $displayed = ContactNameRepresentation::factory()->create(['contact_id' => $contact->id, 'formatted_name' => 'Dr Robert California', 'given_name' => 'Robert', 'family_name' => 'California']);
        $contact->display_name_representation_id = $displayed->id;
        $contact->save();

        new UpdateContactName(user: $user, contact: $contact, firstName: 'Robert', lastName: 'California', nickname: 'The Lizard King')->execute();

        $this->assertDatabaseHas('contact_name_representations', ['id' => $displayed->id, 'formatted_name' => 'Dr Robert California']);
        $this->assertSame('Dr Robert California', $contact->refresh()->name);
    }

    #[Test]
    public function it_drops_the_full_name_once_the_parts_change(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => null, 'last_name' => null]);
        $displayed = ContactNameRepresentation::factory()->create(['contact_id' => $contact->id, 'formatted_name' => 'Dr Robert California', 'given_name' => 'Robert', 'family_name' => 'California']);
        $contact->display_name_representation_id = $displayed->id;
        $contact->save();

        new UpdateContactName(user: $user, contact: $contact, firstName: 'Robert', lastName: 'Lipton')->execute();

        $this->assertDatabaseHas('contact_name_representations', ['id' => $displayed->id, 'formatted_name' => null, 'family_name' => 'Lipton']);
    }
}
