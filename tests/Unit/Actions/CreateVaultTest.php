<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateDefaultValueLists;
use App\Actions\CreateVault;
use App\Actions\UpdateVault;
use App\Enums\DefaultTemplateEnum;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\VaultHasNoContactDefault;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class CreateVaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();

        $vault = new CreateVault(
            user: $user,
            name: 'Dunder Mifflin',
            description: 'Everybody at the Scranton branch',
            colour: '#AA151B',
        )->execute();

        $this->assertInstanceOf(Vault::class, $vault);
        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch',
            'colour' => '#aa151b',
            'position' => 1,
            'contact_count' => 1,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultCreation
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->isAbout($vault)
                && $job->parameters === ['name' => 'Dunder Mifflin'],
        );
    }

    #[Test]
    public function it_makes_the_first_vault_of_an_account_the_one_records_land_in(): void
    {
        config(['monica.free_vault_limit' => 5]);
        Queue::fake();

        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();

        $first = new CreateVault(user: $user, name: 'Dunder Mifflin')->execute();
        $second = new CreateVault(user: $user, name: 'Schrute Farms')->execute();

        $this->assertTrue($first->is_default);
        $this->assertFalse($second->is_default);
    }

    #[Test]
    public function it_puts_a_new_vault_at_the_end_of_the_list(): void
    {
        config(['monica.free_vault_limit' => 5]);
        Queue::fake();

        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();
        Vault::factory()->create(['account_id' => $user->account_id, 'position' => 4]);

        $vault = new CreateVault(user: $user, name: 'Schrute Farms')->execute();

        $this->assertSame(5, $vault->position);
    }

    #[Test]
    public function it_counts_only_the_vaults_of_the_account_when_placing_a_new_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();
        Vault::factory()->create(['position' => 9]);

        $vault = new CreateVault(user: $user, name: 'Dunder Mifflin')->execute();

        $this->assertSame(1, $vault->position);
        $this->assertTrue($vault->is_default);
    }

    #[Test]
    public function it_strips_the_markup_out_of_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();

        $vault = new CreateVault(
            user: $user,
            name: '<script>alert("Dwight")</script>Schrute Farms',
            description: '<b>Beets</b>',
        )->execute();

        $this->assertEquals('Schrute Farms', $vault->name);
        $this->assertEquals('Beets', $vault->description);
    }

    #[Test]
    public function it_gives_the_vault_the_template_new_contacts_start_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'is_system' => true, 'slug' => DefaultTemplateEnum::BasicContact->value]);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $template->structure->update(['published_version_id' => $version->id]);

        $vault = new CreateVault(user: $user, name: 'Dunder Mifflin')->execute();

        $this->assertDatabaseHas('vault_default_templates', [
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'applies_to' => StructureAppliesToEnum::Contact->value,
            'entity_type_id' => null,
            'structure_version_id' => $version->id,
        ]);
    }

    #[Test]
    public function it_gives_the_vault_basic_contact_whatever_other_contact_templates_are_supplied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        new CreateDefaultValueLists(user: $user)->execute();

        foreach ([DefaultTemplateEnum::FullPortrait, DefaultTemplateEnum::Essentials, DefaultTemplateEnum::BasicContact] as $supplied) {
            new CreateDefaultTemplate(user: $user, supplied: $supplied)->handle();
        }

        $vault = new CreateVault(user: $user, name: 'Scranton')->execute();

        $basicContact = Template::query()->where('slug', DefaultTemplateEnum::BasicContact->value)->sole();

        $this->assertDatabaseHas('vault_default_templates', [
            'vault_id' => $vault->id,
            'applies_to' => StructureAppliesToEnum::Contact->value,
            'structure_version_id' => $basicContact->structure->published_version_id,
        ]);
    }

    #[Test]
    public function it_gives_the_vault_the_next_supplied_contact_template_once_basic_contact_is_deleted(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        new CreateDefaultValueLists(user: $user)->execute();

        foreach ([DefaultTemplateEnum::FullPortrait, DefaultTemplateEnum::Essentials, DefaultTemplateEnum::BasicContact] as $supplied) {
            new CreateDefaultTemplate(user: $user, supplied: $supplied)->handle();
        }

        Template::query()->where('slug', DefaultTemplateEnum::BasicContact->value)->sole()->delete();

        $vault = new CreateVault(user: $user, name: 'Scranton')->execute();

        $essentials = Template::query()->where('slug', DefaultTemplateEnum::Essentials->value)->sole();

        $this->assertDatabaseHas('vault_default_templates', [
            'vault_id' => $vault->id,
            'applies_to' => StructureAppliesToEnum::Contact->value,
            'structure_version_id' => $essentials->structure->published_version_id,
        ]);
    }

    #[Test]
    public function it_starts_the_vault_and_its_owner_on_the_version_chosen(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Template::factory()->published()->create(['account_id' => $user->account_id, 'is_system' => true, 'slug' => DefaultTemplateEnum::BasicContact->value]);
        $chosen = Template::factory()->published()->create(['account_id' => $user->account_id, 'name' => 'Paper sales']);

        $vault = new CreateVault(
            user: $user,
            name: 'Dunder Mifflin',
            structureVersion: $chosen->structure->publishedVersion,
        )->execute();

        $this->assertDatabaseHas('vault_default_templates', [
            'vault_id' => $vault->id,
            'applies_to' => StructureAppliesToEnum::Contact->value,
            'structure_version_id' => $chosen->structure->published_version_id,
        ]);
        $this->assertDatabaseHas('contacts', [
            'vault_id' => $vault->id,
            'is_me' => true,
            'structure_version_id' => $chosen->structure->published_version_id,
        ]);
    }

    #[Test]
    public function it_refuses_a_version_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Template::factory()->published()->create(['account_id' => $user->account_id]);
        $elsewhere = Template::factory()->published()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateVault(
            user: $user,
            name: 'Dunder Mifflin',
            structureVersion: $elsewhere->structure->publishedVersion,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_version_of_an_animal_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Template::factory()->published()->create(['account_id' => $user->account_id]);
        $animal = Template::factory()->forEntity()->published()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new CreateVault(
            user: $user,
            name: 'Dunder Mifflin',
            structureVersion: $animal->structure->publishedVersion,
        )->execute();
    }

    #[Test]
    public function it_gives_a_new_vault_the_animal_default(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();
        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();
        $animal = Template::query()->where('account_id', $user->account_id)->where('slug', 'basic-animal')->sole();

        $vault = new CreateVault(user: $user, name: 'Schrute Farms')->execute();

        $this->assertDatabaseHas('vault_default_templates', [
            'vault_id' => $vault->id,
            'applies_to' => StructureAppliesToEnum::Entity->value,
            'entity_type_id' => $animal->structure->entity_type_id,
            'structure_version_id' => $animal->structure->published_version_id,
        ]);
        $this->assertDatabaseHas('vault_default_templates', [
            'vault_id' => $vault->id,
            'applies_to' => StructureAppliesToEnum::Contact->value,
        ]);
    }

    #[Test]
    public function it_writes_down_the_contact_standing_for_the_owner_of_the_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        new CreateDefaultTemplate(user: $user)->handle();
        $template = Template::query()->where('account_id', $user->account_id)->where('slug', DefaultTemplateEnum::BasicContact->value)->sole();

        $vault = new CreateVault(user: $user, name: 'Dunder Mifflin')->execute();

        $contact = $vault->contacts()->sole();
        $this->assertTrue($contact->is_me);
        $this->assertSame('Michael', $contact->first_name);
        $this->assertSame('Scott', $contact->last_name);
        $this->assertSame($template->structure->published_version_id, $contact->structure_version_id);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactCreation
                && $job->vault->id === $vault->id
                && $job->isAbout($contact),
        );
    }

    #[Test]
    public function it_creates_a_vault_for_an_account_at_the_contact_ceiling(): void
    {
        Queue::fake();
        config(['monica.free_contact_limit' => 1, 'monica.free_vault_limit' => 5]);

        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();
        Contact::factory()->create(['account_id' => $user->account_id]);

        $vault = new CreateVault(user: $user, name: 'Schrute Farms')->execute();

        $this->assertTrue($vault->contacts()->sole()->is_me);
        $this->assertSame(1, $user->account->contactCount());
    }

    #[Test]
    public function it_creates_nothing_when_the_account_has_no_contact_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        try {
            new CreateVault(user: $user, name: 'Dunder Mifflin')->execute();
            $this->fail('The vault was created without the contact standing for its owner.');
        } catch (VaultHasNoContactDefault) {
        }

        $this->assertDatabaseMissing('vaults', ['account_id' => $user->account_id]);
        $this->assertDatabaseMissing('vault_default_templates', ['account_id' => $user->account_id]);
        $this->assertDatabaseMissing('contacts', ['account_id' => $user->account_id]);
    }

    #[Test]
    public function it_runs_no_queued_work_when_the_creation_is_rolled_back(): void
    {
        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();
        $processed = [];
        Event::listen(JobProcessing::class, function (JobProcessing $event) use (&$processed): void {
            $processed[] = $event->job->resolveName();
        });

        try {
            DB::transaction(function () use ($user): void {
                new CreateVault(user: $user, name: 'Dunder Mifflin')->execute();

                throw new RuntimeException('The branch was closed before anybody moved in.');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame([], $processed);
        $this->assertDatabaseMissing('vaults', ['account_id' => $user->account_id]);
    }

    #[Test]
    public function it_refuses_a_vault_past_the_ceiling_of_the_free_offer(): void
    {
        Queue::fake();
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(AuthorizationException::class);

        new CreateVault(user: $user, name: 'Sabre')->execute();
    }

    #[Test]
    public function it_keeps_what_an_account_over_the_ceiling_already_has(): void
    {
        Queue::fake();
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();
        $vaults = Vault::factory()->count(3)->create(['account_id' => $user->account_id]);

        try {
            new CreateVault(user: $user, name: 'Sabre')->execute();
        } catch (AuthorizationException) {
        }

        $renamed = new UpdateVault(user: $user, vault: $vaults->first(), name: 'Scranton')->execute();

        $this->assertSame(3, $user->account->vaults()->count());
        $this->assertSame('Scranton', $renamed->refresh()->name);
    }

    #[Test]
    public function it_lifts_the_ceiling_with_full_access(): void
    {
        Queue::fake();
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();
        $user->account->update(['access_granted_at' => now()]);
        Vault::factory()->count(2)->create(['account_id' => $user->account_id]);

        $vault = new CreateVault(user: $user, name: 'Sabre')->execute();

        $this->assertInstanceOf(Vault::class, $vault);
    }

    #[Test]
    public function it_never_caps_a_self_hosted_instance(): void
    {
        Queue::fake();
        config(['monica.free_vault_limit' => 1, 'monica.self_hosted' => true]);

        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();
        Vault::factory()->count(2)->create(['account_id' => $user->account_id]);

        $vault = new CreateVault(user: $user, name: 'Sabre')->execute();

        $this->assertInstanceOf(Vault::class, $vault);
    }
}
