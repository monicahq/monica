<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateContact;
use App\Enums\NameOrderEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\VaultAlreadyHasAMeContact;
use App\Exceptions\VaultHasNoContactDefault;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_a_contact_down(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user->account);

        $contact = new CreateContact(
            user: $user,
            vault: $vault,
            structureVersion: $version,
            prefix: 'Mr',
            firstName: 'Michael',
            middleName: 'Gary',
            lastName: 'Scott',
            suffix: 'Jr',
            nickname: 'Prison Mike',
            nameOrder: NameOrderEnum::GivenFirst,
        )->execute();

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
            'prefix' => 'Mr',
            'first_name' => 'Michael',
            'middle_name' => 'Gary',
            'last_name' => 'Scott',
            'suffix' => 'Jr',
            'nickname' => 'Prison Mike',
            'name_order' => NameOrderEnum::GivenFirst->value,
            'sort_name' => 'scott michael gary',
            'is_me' => false,
            'is_partial' => false,
            'is_archived' => false,
            'is_favourite' => false,
            'life_event_count' => 0,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactCreation
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->isAbout($contact)
                && $job->parameters === ['name' => 'Michael Gary Scott'],
        );
    }

    #[Test]
    public function it_starts_the_contact_on_the_template_the_vault_defaults_to(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user->account);
        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
        ]);

        $contact = new CreateContact(
            user: $user,
            vault: $vault,
            firstName: 'Michael',
            lastName: 'Scott',
        )->execute();

        $this->assertEquals($version->id, $contact->structure_version_id);
        $this->assertSame(1, $version->fresh()->record_count);
    }

    #[Test]
    public function it_prefers_the_version_it_is_given_to_the_one_the_vault_defaults_to(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $this->publishedContactVersion($user->account)->id,
        ]);
        $chosen = $this->publishedContactVersion($user->account);

        $contact = new CreateContact(
            user: $user,
            vault: $vault,
            structureVersion: $chosen,
            firstName: 'Michael',
            lastName: 'Scott',
        )->execute();

        $this->assertEquals($chosen->id, $contact->structure_version_id);
    }

    #[Test]
    public function it_refuses_a_vault_with_nothing_for_a_new_contact_to_start_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(VaultHasNoContactDefault::class);

        new CreateContact(user: $user, vault: $vault, firstName: 'Michael')->execute();
    }

    #[Test]
    public function it_gives_the_contact_a_colour_to_draw_their_initials_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $contact = new CreateContact(
            user: $user,
            vault: $vault,
            structureVersion: $this->publishedContactVersion($user->account),
            firstName: 'Pam',
            lastName: 'Beesly',
        )->execute();

        $this->assertContains($contact->avatar_colour, Contact::AVATAR_COLOURS);
    }

    #[Test]
    public function it_counts_the_contact_in_the_vault_and_on_the_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 4]);
        $version = $this->publishedContactVersion($user->account);

        new CreateContact(
            user: $user,
            vault: $vault,
            structureVersion: $version,
            firstName: 'Jim',
            lastName: 'Halpert',
        )->execute();

        $this->assertSame(5, $vault->fresh()->contact_count);
        $this->assertSame(1, $version->fresh()->record_count);
    }

    #[Test]
    public function it_writes_down_somebody_who_was_only_mentioned(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $contact = new CreateContact(
            user: $user,
            vault: $vault,
            structureVersion: $this->publishedContactVersion($user->account),
            firstName: 'Jack',
            isPartial: true,
        )->execute();

        $this->assertTrue($contact->is_partial);
        $this->assertEquals('jack', $contact->sort_name);
    }

    #[Test]
    public function it_writes_a_contact_down_already_pinned_to_the_top_of_the_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $contact = new CreateContact(
            user: $user,
            vault: $vault,
            structureVersion: $this->publishedContactVersion($user->account),
            firstName: 'Pam',
            isFavourite: true,
        )->execute();

        $this->assertTrue($contact->is_favourite);
    }

    #[Test]
    public function it_writes_down_the_contact_representing_the_user(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $contact = new CreateContact(
            user: $user,
            vault: $vault,
            structureVersion: $this->publishedContactVersion($user->account),
            firstName: 'Michael',
            lastName: 'Scott',
            isMe: true,
        )->execute();

        $this->assertTrue($contact->is_me);
    }

    #[Test]
    public function it_refuses_a_second_contact_representing_the_user_in_the_same_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->me()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        $this->expectException(VaultAlreadyHasAMeContact::class);

        new CreateContact(
            user: $user,
            vault: $vault,
            structureVersion: $this->publishedContactVersion($user->account),
            firstName: 'Michael',
            lastName: 'Scott',
            isMe: true,
        )->execute();
    }

    #[Test]
    public function it_lets_each_vault_hold_its_own_contact_representing_the_user(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $family = Vault::factory()->create(['account_id' => $user->account_id]);
        $work = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user->account);

        Contact::factory()->me()->create(['vault_id' => $family->id, 'account_id' => $user->account_id]);

        $contact = new CreateContact(
            user: $user,
            vault: $work,
            structureVersion: $version,
            firstName: 'Michael',
            lastName: 'Scott',
            isMe: true,
        )->execute();

        $this->assertTrue($contact->is_me);
        $this->assertEquals($work->id, $contact->vault_id);
    }

    #[Test]
    public function it_writes_down_somebody_whose_name_is_not_known(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $contact = new CreateContact(
            user: $user,
            vault: $vault,
            structureVersion: $this->publishedContactVersion($user->account),
            prefix: 'Dr',
            isPartial: true,
        )->execute();

        $this->assertTrue($contact->isNameless());
        $this->assertTrue($contact->is_partial);
        $this->assertEquals(__('Unnamed contact'), $contact->name);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'first_name' => null, 'last_name' => null]);
    }

    #[Test]
    public function it_strips_the_markup_out_of_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $contact = new CreateContact(
            user: $user,
            vault: $vault,
            structureVersion: $this->publishedContactVersion($user->account),
            firstName: '<script>alert("Dwight")</script>Dwight',
            lastName: '<b>Schrute</b>',
        )->execute();

        $this->assertEquals('Dwight', $contact->first_name);
        $this->assertEquals('Schrute', $contact->last_name);
    }

    #[Test]
    public function it_refuses_a_vault_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateContact(
            user: $user,
            vault: $vault,
            structureVersion: $this->publishedContactVersion($user->account),
            firstName: 'Dwight',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_structure_version_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new CreateContact(
            user: $user,
            vault: $vault,
            structureVersion: $this->publishedContactVersion(Account::factory()->create()),
            firstName: 'Dwight',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_template_that_does_not_describe_a_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->forEntity()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $this->expectException(InvalidArgumentException::class);

        new CreateContact(
            user: $user,
            vault: $vault,
            structureVersion: $version,
            firstName: 'Dwight',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_version_that_is_still_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->expectException(InvalidArgumentException::class);

        new CreateContact(
            user: $user,
            vault: $vault,
            structureVersion: $version,
            firstName: 'Dwight',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_contact_past_the_ceiling_of_the_free_offer(): void
    {
        Queue::fake();
        config(['monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user->account);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Full access lifts the ceiling.');

        new CreateContact(user: $user, vault: $vault, structureVersion: $version, firstName: 'Pam')->execute();
    }

    #[Test]
    public function it_counts_archived_contacts_in_every_vault_of_the_account(): void
    {
        Queue::fake();
        config(['monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user->account);
        Contact::factory()->archived()->create(['account_id' => $user->account_id, 'vault_id' => $elsewhere->id]);

        $this->expectException(AuthorizationException::class);

        new CreateContact(user: $user, vault: $vault, structureVersion: $version, firstName: 'Pam')->execute();
    }

    #[Test]
    public function it_leaves_the_me_contact_and_partial_contacts_out_of_the_count_and_never_refuses_them(): void
    {
        Queue::fake();
        config(['monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user->account);
        Contact::factory()->me()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        Contact::factory()->partial()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $pam = new CreateContact(user: $user, vault: $vault, structureVersion: $version, firstName: 'Pam')->execute();
        $partial = new CreateContact(user: $user, vault: $vault, structureVersion: $version, firstName: 'Bob', isPartial: true)->execute();

        $this->assertInstanceOf(Contact::class, $pam);
        $this->assertInstanceOf(Contact::class, $partial);
    }

    #[Test]
    public function it_lifts_the_ceiling_with_full_access(): void
    {
        Queue::fake();
        config(['monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        $this->subscribeAccount($user->account);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user->account);
        Contact::factory()->count(2)->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $contact = new CreateContact(user: $user, vault: $vault, structureVersion: $version, firstName: 'Pam')->execute();

        $this->assertInstanceOf(Contact::class, $contact);
    }

    #[Test]
    public function it_never_caps_a_self_hosted_instance(): void
    {
        Queue::fake();
        config(['monica.free_contact_limit' => 1, 'monica.self_hosted' => true]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $version = $this->publishedContactVersion($user->account);
        Contact::factory()->count(2)->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $contact = new CreateContact(user: $user, vault: $vault, structureVersion: $version, firstName: 'Pam')->execute();

        $this->assertInstanceOf(Contact::class, $contact);
    }

    private function publishedContactVersion(Account $account): StructureVersion
    {
        $template = Template::factory()->create(['account_id' => $account->id]);

        return StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
    }
}
