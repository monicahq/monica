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
        $response->assertSee('href="'.route('admin.accounts.index').'"', escape: false);
    }

    #[Test]
    public function it_keeps_the_deactivation_of_your_own_account_on_screen_but_refused_with_its_reason(): void
    {
        $michael = $this->administrator();

        $response = $this->actingAs($michael)->get(route('admin.accounts.show', ['account' => $michael->account_id]));

        $response->assertSeeInOrder([
            'id="deactivation-refusal"',
            'aria-disabled="true"',
            'aria-describedby="deactivation-refusal"',
        ], escape: false);
    }

    #[Test]
    public function it_offers_to_deactivate_somebody_else(): void
    {
        $dwight = User::factory()->create();

        $response = $this->actingAs($this->administrator())->get(route('admin.accounts.show', ['account' => $dwight->account_id]));

        $response->assertSee($this->deactivateUrl($dwight), escape: false);
        $response->assertDontSee('aria-describedby="deactivation-refusal"', escape: false);
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
    }

    #[Test]
    public function it_shows_the_accounts_in_numbered_pages(): void
    {
        $michael = $this->administrator();
        Account::factory()->count(20)->create();

        $response = $this->actingAs($michael)->get(route('admin.accounts.index'));

        $response->assertStatus(200);
        $response->assertSee('aria-current="page"', escape: false);
        $response->assertSee('1 to 20 of 21', escape: false);
    }

    private function deactivateUrl(User $user): string
    {
        return route('admin.accounts.activation.destroy', ['account' => $user->account_id]);
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
