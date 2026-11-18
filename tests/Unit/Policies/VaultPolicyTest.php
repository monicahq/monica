<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_another_vault_below_the_ceiling(): void
    {
        config(['monica.free_vault_limit' => 2]);

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertTrue(Gate::forUser($user)->allows('create', Vault::class));
    }

    #[Test]
    public function it_refuses_another_vault_at_the_ceiling(): void
    {
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $response = Gate::forUser($user)->inspect('create', Vault::class);

        $this->assertTrue($response->denied());
    }

    #[Test]
    public function it_allows_another_vault_when_the_instance_meters_nobody(): void
    {
        config(['monica.self_hosted' => true, 'monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertTrue(Gate::forUser($user)->allows('create', Vault::class));
    }

    #[Test]
    public function it_refuses_when_nobody_is_signed_in(): void
    {
        config(['monica.self_hosted' => true]);

        $this->assertFalse(Gate::forUser(null)->allows('create', Vault::class));
    }

    #[Test]
    public function it_allows_deleting_the_default_vault_when_the_list_of_vaults_is_shown(): void
    {
        $user = User::factory()->create(['shows_vault_list' => true]);
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);

        $this->assertTrue(Gate::forUser($user)->allows('delete', $vault));
    }

    #[Test]
    public function it_refuses_deleting_the_default_vault_when_the_list_of_vaults_is_hidden(): void
    {
        $user = User::factory()->create(['shows_vault_list' => false]);
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);

        $response = Gate::forUser($user)->inspect('delete', $vault);

        $this->assertTrue($response->denied());
        $this->assertSame('You cannot delete your default vault while the list of vaults is turned off. Turn the list back on in Preferences first.', $response->message());
    }

    #[Test]
    public function it_allows_deleting_another_vault_when_the_list_of_vaults_is_hidden(): void
    {
        $user = User::factory()->create(['shows_vault_list' => false]);
        Vault::factory()->default()->create(['account_id' => $user->account_id]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertTrue(Gate::forUser($user)->allows('delete', $vault));
    }

    #[Test]
    public function it_refuses_deleting_when_nobody_is_signed_in(): void
    {
        $vault = Vault::factory()->create();

        $this->assertFalse(Gate::forUser(null)->allows('delete', $vault));
    }
}
