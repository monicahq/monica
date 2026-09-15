<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateVault;
use App\Actions\UpdateVault;
use App\Enums\StructureAppliesToEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateVaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();

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
            'contact_count' => 0,
        ]);
    }

    #[Test]
    public function it_makes_the_first_vault_of_an_account_the_one_records_land_in(): void
    {
        config(['monica.free_vault_limit' => 5]);
        Queue::fake();

        $user = User::factory()->create();

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
        Vault::factory()->create(['account_id' => $user->account_id, 'position' => 4]);

        $vault = new CreateVault(user: $user, name: 'Schrute Farms')->execute();

        $this->assertSame(5, $vault->position);
    }

    #[Test]
    public function it_counts_only_the_vaults_of_the_account_when_placing_a_new_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Vault::factory()->create(['position' => 9]);

        $vault = new CreateVault(user: $user, name: 'Dunder Mifflin')->execute();

        $this->assertSame(1, $vault->position);
        $this->assertTrue($vault->is_default);
    }

    #[Test]
    public function it_gives_a_vault_a_colour_when_nobody_picks_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $vault = new CreateVault(user: $user, name: 'Dunder Mifflin')->execute();

        $this->assertEquals(Vault::DEFAULT_COLOUR, $vault->colour);
    }

    #[Test]
    public function it_strips_the_markup_out_of_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $vault = new CreateVault(
            user: $user,
            name: '<script>alert("Dwight")</script>Schrute Farms',
            description: '<b>Beets</b>',
        )->execute();

        $this->assertEquals('Schrute Farms', $vault->name);
        $this->assertEquals('Beets', $vault->description);
    }

    #[Test]
    public function it_leaves_the_description_empty_when_there_is_none(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $vault = new CreateVault(user: $user, name: 'Dunder Mifflin', description: '  ')->execute();

        $this->assertNull($vault->description);
    }

    #[Test]
    public function it_gives_the_vault_the_template_new_contacts_start_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'is_system' => true]);
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
    public function it_leaves_the_vault_without_a_default_while_the_account_has_no_supplied_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $vault = new CreateVault(user: $user, name: 'Dunder Mifflin')->execute();

        $this->assertEquals(0, VaultDefaultTemplate::query()->where('vault_id', $vault->id)->count());
    }

    #[Test]
    public function it_logs_the_creation(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $vault = new CreateVault(user: $user, name: 'Dunder Mifflin')->execute();

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
    public function it_refuses_a_vault_past_the_ceiling_of_the_free_offer(): void
    {
        Queue::fake();
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Full access lifts the ceiling.');

        new CreateVault(user: $user, name: 'Sabre')->execute();
    }

    #[Test]
    public function it_keeps_what_an_account_over_the_ceiling_already_has(): void
    {
        Queue::fake();
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
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
        Vault::factory()->count(2)->create(['account_id' => $user->account_id]);

        $vault = new CreateVault(user: $user, name: 'Sabre')->execute();

        $this->assertInstanceOf(Vault::class, $vault);
    }
}
