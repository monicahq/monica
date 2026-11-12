<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateVault;
use App\Actions\DestroyVault;
use App\Enums\UserActionEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Group;
use App\Models\ShareLink;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyVaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_the_vault_softly(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Schrute Farms',
        ]);
        Vault::factory()->default()->create(['account_id' => $user->account_id]);

        new DestroyVault(user: $user, vault: $vault)->execute();

        $this->assertSoftDeleted('vaults', ['id' => $vault->id]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultDeletion
                && $job->account->id === $user->account_id
                && $job->vault->id === $vault->id
                && $job->parameters === ['name' => 'Schrute Farms'],
        );
    }

    #[Test]
    public function it_takes_the_contact_standing_for_the_owner_with_the_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        new CreateDefaultTemplate(user: $user)->handle();
        $vault = new CreateVault(user: $user, name: 'Schrute Farms')->execute();
        $me = $vault->contacts()->where('is_me', true)->sole();

        new DestroyVault(user: $user, vault: $vault)->execute();

        $this->assertSoftDeleted('contacts', [
            'id' => $me->id,
            'deleted_with_vault' => true,
        ]);
    }

    #[Test]
    public function it_deletes_the_last_vault_of_an_account_and_leaves_it_holding_nothing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);
        Vault::factory()->create();

        new DestroyVault(user: $user, vault: $vault)->execute();

        $this->assertSoftDeleted('vaults', ['id' => $vault->id]);
        $this->assertFalse($vault->refresh()->is_default);
        $this->assertEquals(0, Vault::query()->where('account_id', $user->account_id)->count());
    }

    #[Test]
    public function it_makes_the_next_vault_of_an_emptied_account_the_default_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultTemplate(user: $user)->handle();
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);

        new DestroyVault(user: $user, vault: $vault)->execute();

        $next = new CreateVault(user: $user, name: 'Schrute Farms')->execute();

        $this->assertTrue($next->is_default);
    }

    #[Test]
    public function it_takes_the_contacts_of_the_vault_with_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->published()->used(2)->create();
        $dwight = Contact::factory()->create(['vault_id' => $vault->id, 'structure_version_id' => $version->id]);
        $angela = Contact::factory()->create(['vault_id' => $vault->id, 'structure_version_id' => $version->id]);

        new DestroyVault(user: $user, vault: $vault)->execute();

        $this->assertSoftDeleted('contacts', ['id' => $dwight->id]);
        $this->assertSoftDeleted('contacts', ['id' => $angela->id]);
        $this->assertEquals($vault->refresh()->deleted_at, $dwight->refresh()->deleted_at);
        $this->assertEquals(0, $version->refresh()->record_count);
    }

    #[Test]
    public function it_takes_the_records_of_the_invented_kinds_with_it_too(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'entity_count' => 2]);
        $version = StructureVersion::factory()->published()->used(2)->create();
        $dunder = Entity::factory()->create([
            'vault_id' => $vault->id,
            'entity_type_id' => $type->id,
            'structure_version_id' => $version->id,
        ]);
        $sabre = Entity::factory()->create([
            'vault_id' => $vault->id,
            'entity_type_id' => $type->id,
            'structure_version_id' => $version->id,
        ]);

        new DestroyVault(user: $user, vault: $vault)->execute();

        $this->assertSoftDeleted('entities', ['id' => $dunder->id, 'deleted_with_vault' => true]);
        $this->assertSoftDeleted('entities', ['id' => $sabre->id, 'deleted_with_vault' => true]);
        $this->assertEquals($vault->refresh()->deleted_at, $dunder->refresh()->deleted_at);
        $this->assertEquals(0, $type->refresh()->entity_count);
        $this->assertEquals(0, $version->refresh()->record_count);
    }

    #[Test]
    public function it_takes_the_groups_of_the_vault_with_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $branch = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Scranton branch']);
        $elsewhere = Group::factory()->create([
            'vault_id' => Vault::factory()->default()->create(['account_id' => $user->account_id])->id,
            'name' => 'Stamford branch',
        ]);

        new DestroyVault(user: $user, vault: $vault)->execute();

        $this->assertSoftDeleted('groups', ['id' => $branch->id, 'deleted_with_vault' => true]);
        $this->assertEquals($vault->refresh()->deleted_at, $branch->refresh()->deleted_at);
        $this->assertNotSoftDeleted('groups', ['id' => $elsewhere->id, 'deleted_with_vault' => false]);
    }

    #[Test]
    public function it_hands_the_default_over_to_the_first_vault_left(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create([
            'account_id' => $user->account_id,
            'position' => 1,
        ]);
        $next = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        $last = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 3]);

        new DestroyVault(user: $user, vault: $vault)->execute();

        $this->assertFalse($vault->refresh()->is_default);
        $this->assertTrue($next->refresh()->is_default);
        $this->assertFalse($last->refresh()->is_default);
    }

    #[Test]
    public function it_leaves_the_default_where_it_is_when_another_vault_goes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $default = Vault::factory()->default()->create(['account_id' => $user->account_id]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        new DestroyVault(user: $user, vault: $vault)->execute();

        $this->assertTrue($default->refresh()->is_default);
    }

    #[Test]
    public function it_refuses_a_vault_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);
        $vault = Vault::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyVault(user: $user, vault: $vault)->execute();
    }

    #[Test]
    public function it_revokes_every_link_into_the_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);
        $ontoTheVault = ShareLink::factory()->forVault($vault)->create();
        $ontoTheContact = ShareLink::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'shareable_type' => Contact::class,
            'shareable_id' => $contact->id,
        ]);
        $elsewhere = ShareLink::factory()->create();

        new DestroyVault(user: $user, vault: $vault)->execute();

        $this->assertNotNull($ontoTheVault->refresh()->revoked_at);
        $this->assertNotNull($ontoTheContact->refresh()->revoked_at);
        $this->assertNull($elsewhere->refresh()->revoked_at);
    }

    #[Test]
    public function it_takes_the_records_of_the_vault_out_of_search(): void
    {
        Queue::fake();
        $engine = $this->useRecordingSearchEngine();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Vault::factory()->default()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $farm = Entity::factory()->create(['vault_id' => $vault->id]);

        new DestroyVault(user: $user, vault: $vault)->execute();

        $this->assertTrue($engine->wasRemoved($dwight));
        $this->assertTrue($engine->wasRemoved($farm));
    }
}
