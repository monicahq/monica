<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_another_contact_below_the_ceiling(): void
    {
        config(['monica.free_contact_limit' => 2]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $this->assertTrue(Gate::forUser($user)->allows('create', Contact::class));
    }

    #[Test]
    public function it_refuses_another_contact_at_the_ceiling(): void
    {
        config(['monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $response = Gate::forUser($user)->inspect('create', Contact::class);

        $this->assertTrue($response->denied());
    }

    #[Test]
    public function it_allows_another_contact_when_the_instance_meters_nobody(): void
    {
        config(['monica.self_hosted' => true, 'monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $this->assertTrue(Gate::forUser($user)->allows('create', Contact::class));
    }

    #[Test]
    public function it_refuses_deleting_the_contact_representing_the_account_holder(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $me = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'is_me' => true]);

        $response = Gate::forUser($user)->inspect('delete', $me);

        $this->assertTrue($response->denied());
    }

    #[Test]
    public function it_allows_deleting_any_other_contact(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $this->assertTrue(Gate::forUser($user)->allows('delete', $dwight));
    }

    #[Test]
    public function it_refuses_when_nobody_is_signed_in(): void
    {
        config(['monica.self_hosted' => true]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $this->assertFalse(Gate::forUser(null)->allows('create', Contact::class));
        $this->assertFalse(Gate::forUser(null)->allows('delete', $dwight));
    }
}
