<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

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
            'access_granted_at' => now()->subYear(),
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
        $response->assertSee('5 MB of 10 GB', escape: false);
        $response->assertSee('Lifetime access', escape: false);
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

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
