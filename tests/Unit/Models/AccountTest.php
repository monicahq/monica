<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\EmailSent;
use App\Models\Log;
use App\Models\NotificationPreference;
use App\Models\ShareLink;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_one_user(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($account->user()->exists());
        $this->assertTrue($user->is($account->user));
    }

    #[Test]
    public function it_has_one_set_of_notification_preferences(): void
    {
        $preference = NotificationPreference::factory()->create();

        $this->assertTrue($preference->account->notificationPreference()->exists());
    }

    #[Test]
    public function it_has_many_vaults(): void
    {
        $account = Account::factory()->create();
        Vault::factory()->create(['account_id' => $account->id, 'name' => 'Schrute Farms']);

        $this->assertTrue($account->vaults()->exists());
        $this->assertEquals('Schrute Farms', $account->vaults->first()->name);
    }

    #[Test]
    public function it_has_many_value_lists(): void
    {
        $account = Account::factory()->create();
        ValueList::factory()->create(['account_id' => $account->id, 'name' => 'Phone labels']);

        $this->assertTrue($account->valueLists()->exists());
        $this->assertEquals('Phone labels', $account->valueLists->first()->name);
    }

    #[Test]
    public function it_has_many_templates(): void
    {
        $account = Account::factory()->create();
        Template::factory()->create(['account_id' => $account->id, 'name' => 'Schrute Farms guest']);

        $this->assertTrue($account->templates()->exists());
        $this->assertEquals('Schrute Farms guest', $account->templates->first()->name);
    }

    #[Test]
    public function it_has_many_logs(): void
    {
        $account = Account::factory()->create();
        Log::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($account->logs()->exists());
        $this->assertEquals(1, $account->logs()->count());
    }

    #[Test]
    public function it_has_many_emails_sent(): void
    {
        $account = Account::factory()->create();
        EmailSent::factory()->create([
            'account_id' => $account->id,
            'subject' => 'Somebody signed in from a new place',
        ]);

        $this->assertTrue($account->emailsSent()->exists());
        $this->assertEquals('Somebody signed in from a new place', $account->emailsSent->first()->subject);
    }

    #[Test]
    public function it_has_many_share_links(): void
    {
        $account = Account::factory()->create();
        ShareLink::factory()->create([
            'account_id' => $account->id,
            'label' => 'For the family',
        ]);

        $this->assertTrue($account->shareLinks()->exists());
        $this->assertEquals('For the family', $account->shareLinks->first()->label);
    }

    #[Test]
    public function it_casts_the_purchase_the_onboarding_and_the_storage(): void
    {
        $account = Account::factory()->onboarded()->withLifetimeAccess()->create([
            'storage_used_in_bytes' => 2048,
            'storage_limit_in_bytes' => 4096,
        ]);

        $account->refresh();

        $this->assertFalse($account->is_self_hosted);
        $this->assertTrue($account->has_lifetime_access);
        $this->assertInstanceOf(CarbonImmutable::class, $account->purchased_at);
        $this->assertInstanceOf(CarbonImmutable::class, $account->onboarding_completed_at);
        $this->assertSame(2048, $account->storage_used_in_bytes);
        $this->assertSame(4096, $account->storage_limit_in_bytes);
    }

    #[Test]
    public function it_has_no_purchase_and_no_onboarding_date_by_default(): void
    {
        $account = Account::factory()->create();

        $this->assertFalse($account->has_lifetime_access);
        $this->assertNull($account->purchased_at);
        $this->assertNull($account->onboarding_completed_at);
        $this->assertNull($account->billing_email);
        $this->assertSame(0, $account->storage_used_in_bytes);
    }

    #[Test]
    public function it_is_metered_on_the_cloud_instance_and_never_when_self_hosted(): void
    {
        $cloud = Account::factory()->create(['storage_limit_in_bytes' => 4096]);
        $selfHosted = Account::factory()->selfHosted()->create();
        $withoutCeiling = Account::factory()->create(['storage_limit_in_bytes' => null]);

        $this->assertTrue($cloud->isMetered());
        $this->assertFalse($selfHosted->isMetered());
        $this->assertNull($selfHosted->storage_limit_in_bytes);
        $this->assertFalse($withoutCeiling->isMetered());
    }

    #[Test]
    public function it_reads_back_the_name_of_the_person_on_it(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);

        $this->assertEquals('Michael Scott', $michael->account->ownerName());
    }

    #[Test]
    public function it_still_reads_back_a_name_for_somebody_who_was_deleted(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        $account = $michael->account;
        $michael->delete();

        $this->assertEquals('Michael Scott', $account->ownerName());
    }

    #[Test]
    public function it_says_so_when_there_is_nobody_on_it(): void
    {
        $this->assertEquals('an account with nobody on it', Account::factory()->create()->ownerName());
    }

    #[Test]
    public function it_hands_back_a_deleted_person_when_asked_for_one(): void
    {
        $michael = User::factory()->create();
        $account = $michael->account;
        $michael->delete();

        $this->assertNull($account->user);
        $this->assertTrue($michael->is($account->userIncludingDeleted));
    }
}
