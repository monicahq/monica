<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Enums\AccessKindEnum;
use App\Helpers\StripeCatalogue;
use App\Models\Account;
use App\Models\Contact;
use App\Models\StripeOrder;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Admin\AccountViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_an_account_after_the_person_on_it_and_after_its_number_when_nobody_is(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        $empty = Account::factory()->create();

        $this->assertSame('Michael Scott', new AccountViewModel(account: $michael->account)->title());
        $this->assertSame("Account {$empty->id}", new AccountViewModel(account: $empty)->title());
    }

    #[Test]
    public function it_says_where_the_account_runs(): void
    {
        $account = Account::factory()->create();

        $this->assertSame('Hosted by us', new AccountViewModel(account: $account)->whereItRuns());

        config(['monica.self_hosted' => true]);

        $this->assertSame('Self-hosted', new AccountViewModel(account: $account)->whereItRuns());
    }

    #[Test]
    public function it_does_not_ask_a_self_hosted_account_for_a_billing_address(): void
    {
        config(['monica.self_hosted' => true]);

        $account = Account::factory()->create(['billing_email' => 'accounts@dundermifflin.com']);

        $this->assertSame('Not applicable', new AccountViewModel(account: $account)->billingAddressLine());
    }

    #[Test]
    public function it_says_when_a_billable_account_gave_no_address(): void
    {
        $account = Account::factory()->create(['billing_email' => null]);

        $this->assertSame('None given', new AccountViewModel(account: $account)->billingAddressLine());
    }

    #[Test]
    public function it_writes_the_storage_against_the_limit_when_there_is_one(): void
    {
        $limited = Account::factory()->create([
            'storage_used_in_bytes' => 1024,
            'storage_limit_in_bytes' => 4096,
        ]);
        $unlimited = Account::factory()->create([
            'storage_used_in_bytes' => 1024,
            'storage_limit_in_bytes' => null,
        ]);

        $this->assertSame('1 KB of 4 KB', new AccountViewModel(account: $limited)->storageLine());
        $this->assertSame('1 KB, no limit', new AccountViewModel(account: $unlimited)->storageLine());
    }

    #[Test]
    public function it_badges_what_the_account_is_and_what_state_its_person_is_in(): void
    {
        config(['monica.self_hosted' => true]);

        $account = Account::factory()->create();
        StripeOrder::factory()->settled()->create(['account_id' => $account->id]);

        $labels = array_column(new AccountViewModel(account: $account)->badges(), 'label');

        $this->assertSame(['Self-hosted', 'Lifetime access', 'Nobody on this account'], $labels);
    }

    #[Test]
    public function it_badges_a_person_who_cannot_sign_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $badges = new AccountViewModel(account: $user->account)->badges();

        $this->assertSame([['label' => 'Deactivated', 'tone' => 'danger']], $badges);
    }

    #[Test]
    public function it_badges_a_person_who_was_deleted_ahead_of_one_who_was_deactivated(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $account = $user->account;
        $user->delete();

        $labels = array_column(new AccountViewModel(account: $account)->badges(), 'label');

        $this->assertSame(['Person deleted'], $labels);
    }

    #[Test]
    public function it_says_in_the_notice_when_the_person_was_deleted_and_says_nothing_otherwise(): void
    {
        $user = User::factory()->create();
        $account = $user->account;

        $this->assertNull(new AccountViewModel(account: $account)->deletionNotice());

        $user->delete();

        $this->assertStringContainsString(
            'The person on this account was deleted on',
            (string) new AccountViewModel(account: $account->refresh())->deletionNotice(),
        );
    }

    #[Test]
    public function it_knows_the_operator_is_looking_at_their_own_account(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();

        $this->assertTrue(new AccountViewModel(account: $michael->account, viewer: $michael)->isTheViewer());
        $this->assertFalse(new AccountViewModel(account: $michael->account, viewer: $dwight)->isTheViewer());
        $this->assertFalse(new AccountViewModel(account: $michael->account)->isTheViewer());
    }

    #[Test]
    public function support_may_act_on_an_account_with_somebody_on_it_who_is_still_there(): void
    {
        $user = User::factory()->create();
        $account = $user->account;

        $this->assertTrue(new AccountViewModel(account: $account)->isActionable());

        $user->delete();

        $this->assertFalse(new AccountViewModel(account: $account->refresh())->isActionable());
        $this->assertFalse(new AccountViewModel(account: Account::factory()->create())->isActionable());
    }

    #[Test]
    public function it_says_how_the_person_signs_in_and_whether_they_are_asked_for_a_second_factor(): void
    {
        $plain = User::factory()->create();
        $viewModel = new AccountViewModel(account: $plain->account);

        $this->assertSame('With a password', $viewModel->signInLine());
        $this->assertSame('Off', $viewModel->twoFactorLine());
        $this->assertSame('Yes', $viewModel->canSignInLine());
        $this->assertSame('Never', $viewModel->lastSignedInLine());
    }

    #[Test]
    public function it_hands_back_the_person_on_the_account(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);

        $viewModel = new AccountViewModel(account: $michael->account);

        $this->assertTrue($viewModel->hasUser());
        $this->assertEquals('Michael Scott', $viewModel->user()->name);
        $this->assertFalse($viewModel->userWasDeleted());
    }

    #[Test]
    public function it_still_hands_back_a_person_who_was_deleted(): void
    {
        $michael = User::factory()->create();
        $account = $michael->account;
        $michael->delete();

        $viewModel = new AccountViewModel(account: $account);

        $this->assertTrue($viewModel->hasUser());
        $this->assertTrue($viewModel->userWasDeleted());
    }

    #[Test]
    public function it_copes_with_an_account_nobody_is_on(): void
    {
        $viewModel = new AccountViewModel(account: Account::factory()->create());

        $this->assertFalse($viewModel->hasUser());
        $this->assertNull($viewModel->user());
        $this->assertFalse($viewModel->userWasDeleted());
    }

    #[Test]
    public function it_counts_what_the_account_holds_without_naming_any_of_it(): void
    {
        $account = Account::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $account->id]);
        Contact::factory()->count(3)->create([
            'account_id' => $account->id,
            'vault_id' => $vault->id,
        ]);

        $viewModel = new AccountViewModel(account: $account);

        $this->assertEquals(1, $viewModel->vaultCount());
        $this->assertEquals(3, $viewModel->contactCount());
    }

    #[Test]
    public function it_counts_nothing_for_an_account_holding_no_vault(): void
    {
        $viewModel = new AccountViewModel(account: Account::factory()->create());

        $this->assertEquals(0, $viewModel->vaultCount());
        $this->assertEquals(0, $viewModel->contactCount());
    }

    #[Test]
    public function it_shows_storage_in_a_unit_somebody_reads(): void
    {
        $account = Account::factory()->create([
            'storage_used_in_bytes' => 5 * 1024 * 1024,
            'storage_limit_in_bytes' => 1024 * 1024 * 1024,
        ]);

        $viewModel = new AccountViewModel(account: $account);

        $this->assertEquals('5 MB', $viewModel->storageUsed());
        $this->assertEquals('1 GB', $viewModel->storageLimit());
        $this->assertTrue($viewModel->hasStorageLimit());
    }

    #[Test]
    public function it_counts_bytes_as_bytes_while_they_still_are(): void
    {
        $account = Account::factory()->create(['storage_used_in_bytes' => 512]);

        $this->assertEquals('512 B', new AccountViewModel(account: $account)->storageUsed());
    }

    #[Test]
    public function it_reads_an_account_with_no_ceiling_as_unlimited_rather_than_as_zero(): void
    {
        $account = Account::factory()->create(['storage_limit_in_bytes' => null]);

        $viewModel = new AccountViewModel(account: $account);

        $this->assertFalse($viewModel->hasStorageLimit());
        $this->assertNull($viewModel->storageLimit());
        $this->assertFalse($viewModel->isOverStorageLimit());
    }

    #[Test]
    public function it_knows_an_account_is_holding_more_than_it_is_allowed(): void
    {
        $account = Account::factory()->create([
            'storage_used_in_bytes' => 2048,
            'storage_limit_in_bytes' => 1024,
        ]);

        $this->assertTrue(new AccountViewModel(account: $account)->isOverStorageLimit());
    }

    #[Test]
    public function it_treats_billing_as_beside_the_point_on_a_self_hosted_account(): void
    {
        $account = Account::factory()->create();

        $this->assertTrue(new AccountViewModel(account: $account)->isBillable());

        config(['monica.self_hosted' => true]);

        $this->assertFalse(new AccountViewModel(account: $account)->isBillable());
    }

    #[Test]
    public function it_shows_the_paid_ceiling_of_an_account_that_holds_full_access(): void
    {
        config(['monica.paid_storage_limit_in_bytes' => 10737418240]);

        $account = Account::factory()->create(['storage_limit_in_bytes' => 104857600, 'storage_used_in_bytes' => 5242880]);

        $this->assertSame('100 MB', new AccountViewModel(account: $account)->storageLimit());

        $account->update(['access_granted_at' => now()]);

        $viewModel = new AccountViewModel(account: $account->fresh());

        $this->assertTrue($viewModel->hasStorageLimit());
        $this->assertSame('10 GB', $viewModel->storageLimit());
        $this->assertFalse($viewModel->isOverStorageLimit());
        $this->assertSame(104857600, $account->fresh()->storage_limit_in_bytes);
    }

    #[Test]
    public function it_reads_an_account_over_the_paid_ceiling_as_over_it(): void
    {
        config(['monica.paid_storage_limit_in_bytes' => 4096]);

        $account = Account::factory()->create([
            'access_granted_at' => now(),
            'storage_limit_in_bytes' => 1024,
            'storage_used_in_bytes' => 8192,
        ]);

        $this->assertTrue(new AccountViewModel(account: $account)->isOverStorageLimit());
    }

    #[Test]
    public function it_reads_the_order_and_the_subscription_the_way_the_owner_screen_does(): void
    {
        StripeCatalogue::forget();
        $this->fakeStripe();

        $account = Account::factory()->create(['stripe_id' => 'cus_dundermifflin']);
        $order = StripeOrder::factory()->refunded()->create(['account_id' => $account->id]);
        $this->subscribeAccount($account);

        $viewModel = new AccountViewModel(account: $account->fresh());

        $this->assertSame($order->id, $viewModel->order()?->id);
        $this->assertSame('refunded', $viewModel->order()?->stripe_status);
        $this->assertSame('Monthly subscription', $viewModel->subscriptionOfferLabel());
        $this->assertSame(AccessKindEnum::Subscription->label(), $viewModel->accessKindLabel());
        $this->assertNotNull($viewModel->renewsAt());
        $this->assertNull($viewModel->endsAt());
    }

    #[Test]
    public function it_speaks_of_no_order_nobody_paid(): void
    {
        $account = Account::factory()->create();
        StripeOrder::factory()->create(['account_id' => $account->id]);

        $viewModel = new AccountViewModel(account: $account->fresh());

        $this->assertNull($viewModel->order());
        $this->assertSame(__('Free plan'), $viewModel->accessKindLabel());
    }

    #[Test]
    public function it_says_when_a_cancelled_subscription_stops(): void
    {
        StripeCatalogue::forget();
        $this->fakeStripe();

        $account = Account::factory()->create(['stripe_id' => 'cus_dundermifflin']);
        $this->subscribeAccount($account, 'price_monthly', 'active', now()->addWeek());

        $viewModel = new AccountViewModel(account: $account->fresh());

        $this->assertNotNull($viewModel->endsAt());
    }

    #[Test]
    public function it_points_at_the_customer_on_the_stripe_dashboard(): void
    {
        config(['cashier.secret' => 'sk_test_dundermifflin']);

        $account = Account::factory()->create(['stripe_id' => 'cus_dundermifflin']);

        $this->assertSame(
            'https://dashboard.stripe.com/test/customers/cus_dundermifflin',
            new AccountViewModel(account: $account)->stripeCustomerUrl(),
        );

        config(['cashier.secret' => 'sk_live_dundermifflin']);

        $this->assertSame(
            'https://dashboard.stripe.com/customers/cus_dundermifflin',
            new AccountViewModel(account: $account)->stripeCustomerUrl(),
        );
    }

    #[Test]
    public function it_points_nowhere_for_an_account_stripe_has_never_heard_of(): void
    {
        $this->assertNull(new AccountViewModel(account: Account::factory()->create())->stripeCustomerUrl());
    }

    #[Test]
    public function it_says_the_day_the_purchase_was_ordered(): void
    {
        $account = Account::factory()->create();
        $order = StripeOrder::factory()->settled()->create(['account_id' => $account->id]);

        $viewModel = new AccountViewModel(account: $account->fresh());

        $this->assertTrue($viewModel->holdsAccessForGood());
        $this->assertSame(
            __('Yes, bought on :date', ['date' => $order->created_at->isoFormat('LL')]),
            $viewModel->accessForGoodLabel(),
        );
    }

    #[Test]
    public function it_says_the_day_a_grant_was_made(): void
    {
        $account = Account::factory()->withGrantedAccess()->create();

        $viewModel = new AccountViewModel(account: $account->fresh());

        $this->assertTrue($viewModel->holdsAccessForGood());
        $this->assertStringContainsString('granted on', $viewModel->accessForGoodLabel());
    }

    #[Test]
    public function it_says_no_when_nothing_is_held_for_good(): void
    {
        $account = Account::factory()->create();
        StripeOrder::factory()->refunded()->create(['account_id' => $account->id]);

        $viewModel = new AccountViewModel(account: $account->fresh());

        $this->assertFalse($viewModel->holdsAccessForGood());
        $this->assertSame(__('No'), $viewModel->accessForGoodLabel());
    }
}
