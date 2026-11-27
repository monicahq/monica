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
    public function it_narrows_the_list_by_the_filters_in_the_address(): void
    {
        $toby = User::factory()->create(['first_name' => 'Toby', 'last_name' => 'Flenderson', 'is_active' => false]);
        $toby->account->features()->activate('marketplace');
        User::factory()->create(['first_name' => 'Jim', 'last_name' => 'Halpert', 'is_active' => false]);

        $response = $this->actingAs($this->administrator())
            ->get(route('admin.accounts.index', ['state' => 'deactivated', 'flag' => 'marketplace']));

        $response->assertStatus(200);
        $response->assertSee('Toby Flenderson');
        $response->assertDontSee('Jim Halpert');
        $response->assertSee('role="search"', escape: false);
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
    public function it_keeps_the_deletion_of_your_own_account_on_screen_but_refused_with_its_reason(): void
    {
        $michael = $this->administrator();

        $response = $this->actingAs($michael)->get(route('admin.accounts.show', ['account' => $michael->account_id]));

        $response->assertSee('Delete this account');
        $response->assertSeeInOrder([
            'id="account-deletion-refusal"',
            'aria-disabled="true"',
            'aria-describedby="account-deletion-refusal"',
        ], escape: false);
        $response->assertDontSee('name="confirmation"', escape: false);
    }

    #[Test]
    public function it_offers_to_delete_somebody_else_once_their_address_is_typed(): void
    {
        $dwight = User::factory()->create(['email' => 'dwight.schrute@dundermifflin.com']);

        $response = $this->actingAs($this->administrator())->get(route('admin.accounts.show', ['account' => $dwight->account_id]));

        $response->assertSee('Type dwight.schrute@dundermifflin.com to confirm.');
        $response->assertSee('name="confirmation"', escape: false);
    }

    #[Test]
    public function it_deletes_an_account_and_returns_to_the_list(): void
    {
        $dwight = User::factory()->create(['email' => 'dwight.schrute@dundermifflin.com']);
        $account = $dwight->account;

        $response = $this->actingAs($this->administrator())
            ->delete(route('admin.accounts.destroy', ['account' => $account->id]), [
                'confirmation' => ' Dwight.Schrute@DunderMifflin.com ',
            ]);

        $response->assertRedirect(route('admin.accounts.index'));
        $response->assertSessionHas('status', 'The account is deleted.');
        $this->assertModelMissing($account);
    }

    #[Test]
    public function it_refuses_a_deletion_confirmed_with_another_address(): void
    {
        $dwight = User::factory()->create(['email' => 'dwight.schrute@dundermifflin.com']);

        $response = $this->actingAs($this->administrator())
            ->delete(route('admin.accounts.destroy', ['account' => $dwight->account_id]), [
                'confirmation' => 'mose.schrute@dundermifflin.com',
            ]);

        $response->assertSessionHasErrors('confirmation');
        $this->assertModelExists($dwight->account);
    }

    #[Test]
    public function it_asks_for_the_account_number_when_nobody_is_on_the_account(): void
    {
        $account = Account::factory()->create();

        $response = $this->actingAs($this->administrator())
            ->delete(route('admin.accounts.destroy', ['account' => $account->id]), [
                'confirmation' => (string) $account->id,
            ]);

        $response->assertRedirect(route('admin.accounts.index'));
        $this->assertModelMissing($account);
    }

    #[Test]
    public function it_tells_an_administrator_that_their_own_account_cannot_be_deleted_here(): void
    {
        $michael = $this->administrator();

        $response = $this->actingAs($michael)
            ->from(route('admin.accounts.show', ['account' => $michael->account_id]))
            ->delete(route('admin.accounts.destroy', ['account' => $michael->account_id]), [
                'confirmation' => $michael->email,
            ]);

        $response->assertRedirect(route('admin.accounts.show', ['account' => $michael->account_id]));
        $response->assertSessionHasErrors(['deletion' => 'You cannot delete your own account from here. Delete it from your account settings instead.']);
        $this->assertModelExists($michael->account);
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
