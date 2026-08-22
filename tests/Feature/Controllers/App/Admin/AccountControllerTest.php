<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Account;
use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_closed_to_somebody_who_does_not_run_the_instance(): void
    {
        $jim = User::factory()->create();
        $account = Account::factory()->create();

        $this->actingAs($jim)->get(route('admin.accounts.index'))->assertNotFound();
        $this->actingAs($jim)->get(route('admin.accounts.show', ['account' => $account->id]))->assertNotFound();
    }

    #[Test]
    public function it_finds_an_account_by_address(): void
    {
        $dwight = User::factory()->create([
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'email' => 'dwight.schrute@dundermifflin.com',
        ]);

        $response = $this->actingAs($this->administrator())
            ->get(route('admin.accounts.index', ['query' => 'dwight.schrute@dundermifflin.com']));

        $response->assertStatus(200);
        $response->assertSee('Dwight Schrute', escape: false);
        $response->assertSee(route('admin.accounts.show', ['account' => $dwight->account_id]), escape: false);
    }

    #[Test]
    public function it_says_so_plainly_when_a_search_matches_nothing(): void
    {
        $response = $this->actingAs($this->administrator())
            ->get(route('admin.accounts.index', ['query' => 'nobody-here']));

        $response->assertStatus(200);
        $response->assertSee('Nothing matches that', escape: false);
    }

    #[Test]
    public function it_shows_what_an_operator_needs_to_answer_a_support_email(): void
    {
        $dwight = User::factory()->create([
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'email' => 'dwight.schrute@dundermifflin.com',
            'email_verified_at' => now(),
            'last_login_at' => now()->subDays(2),
        ]);
        $dwight->account->update([
            'has_lifetime_access' => true,
            'purchased_at' => now()->subYear(),
            'billing_email' => 'accounts@schrutefarms.com',
            'storage_used_in_bytes' => 5 * 1024 * 1024,
            'storage_limit_in_bytes' => 1024 * 1024 * 1024,
        ]);

        $response = $this->actingAs($this->administrator())
            ->get(route('admin.accounts.show', ['account' => $dwight->account_id]));

        $response->assertStatus(200);
        $response->assertSee('Dwight Schrute', escape: false);
        $response->assertSee('dwight.schrute@dundermifflin.com', escape: false);
        $response->assertSee('accounts@schrutefarms.com', escape: false);
        $response->assertSee('5 MB of 1 GB', escape: false);
        $response->assertSee('Lifetime access', escape: false);
    }

    #[Test]
    public function it_reads_an_account_with_no_ceiling_as_unlimited(): void
    {
        $account = Account::factory()->create(['storage_limit_in_bytes' => null, 'storage_used_in_bytes' => 0]);

        $response = $this->actingAs($this->administrator())
            ->get(route('admin.accounts.show', ['account' => $account->id]));

        $response->assertSee('no limit', escape: false);
    }

    #[Test]
    public function it_marks_the_facts_that_mean_nothing_on_a_self_hosted_account(): void
    {
        $account = Account::factory()->create(['is_self_hosted' => true, 'billing_email' => null]);

        $response = $this->actingAs($this->administrator())
            ->get(route('admin.accounts.show', ['account' => $account->id]));

        $response->assertSee('Not applicable', escape: false);
        $response->assertSee('Self-hosted', escape: false);
    }

    #[Test]
    public function it_still_renders_for_an_account_whose_person_was_deleted(): void
    {
        $dwight = User::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);
        $account = $dwight->account;
        $dwight->delete();

        $response = $this->actingAs($this->administrator())
            ->get(route('admin.accounts.show', ['account' => $account->id]));

        $response->assertStatus(200);
        $response->assertSee('Person deleted', escape: false);
        $response->assertSee('The person on this account was deleted', escape: false);
    }

    #[Test]
    public function it_still_renders_for_an_account_holding_no_vault(): void
    {
        $account = Account::factory()->create();

        $response = $this->actingAs($this->administrator())
            ->get(route('admin.accounts.show', ['account' => $account->id]));

        $response->assertStatus(200);
        $response->assertSee('0 vaults', escape: false);
    }

    #[Test]
    public function it_names_a_deleted_person_in_the_results_rather_than_showing_nobody(): void
    {
        $dwight = User::factory()->create([
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'email' => 'dwight.schrute@dundermifflin.com',
        ]);
        $dwight->delete();

        $response = $this->actingAs($this->administrator())
            ->get(route('admin.accounts.index', ['query' => 'schrute']));

        $response->assertSee('Dwight Schrute', escape: false);
        $response->assertSee('deleted', escape: false);
        $response->assertDontSee('nobody on this account', escape: false);
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
