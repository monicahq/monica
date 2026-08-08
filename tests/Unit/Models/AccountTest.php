<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\EmailSent;
use App\Models\Log;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
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
    public function it_has_many_vaults(): void
    {
        $account = Account::factory()->create();
        Vault::factory()->create(['account_id' => $account->id, 'name' => 'Schrute Farms']);

        $this->assertTrue($account->vaults()->exists());
        $this->assertEquals('Schrute Farms', $account->vaults->first()->name);
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
    public function it_casts_the_purchase_the_onboarding_and_the_storage(): void
    {
        $account = Account::factory()->onboarded()->withLifetimeAccess()->create([
            'storage_used_in_bytes' => 2048,
            'storage_limit_in_bytes' => 4096,
        ]);

        $account->refresh();

        $this->assertFalse($account->is_self_hosted);
        $this->assertTrue($account->has_lifetime_access);
        $this->assertInstanceOf(Carbon::class, $account->purchased_at);
        $this->assertInstanceOf(Carbon::class, $account->onboarding_completed_at);
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
}
