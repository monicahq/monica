<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\EmailSent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($user->account()->exists());
        $this->assertTrue($account->is($user->account));
    }

    #[Test]
    public function it_gives_the_name_the_user_is_called_by(): void
    {
        $user = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);

        $this->assertEquals('Michael Scott', $user->name);
    }

    #[Test]
    public function it_knows_whether_it_has_a_photo(): void
    {
        $withPhoto = User::factory()->create(['photo_path' => 'photos/1/abc']);
        $withoutPhoto = User::factory()->create(['photo_path' => null]);

        $this->assertTrue($withPhoto->hasPhoto());
        $this->assertFalse($withoutPhoto->hasPhoto());
        $this->assertEquals('photos/1/abc_96.webp', $withPhoto->photoVariantPath(96));
        $this->assertEquals([96, 192], User::photoPixelSizes());
    }

    #[Test]
    public function it_has_many_emails_sent(): void
    {
        $user = User::factory()->create();
        EmailSent::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'subject' => 'Somebody signed in from a new place',
        ]);

        $this->assertTrue($user->emailsSent()->exists());
        $this->assertEquals('Somebody signed in from a new place', $user->emailsSent->first()->subject);
    }

    #[Test]
    public function it_returns_the_password_hash_as_the_authentication_password(): void
    {
        $user = User::factory()->create(['password_hash' => Hash::make('dwightschrute')]);

        $this->assertEquals($user->password_hash, $user->getAuthPassword());
        $this->assertTrue(Hash::check('dwightschrute', $user->getAuthPassword()));
    }

    #[Test]
    public function it_returns_an_empty_authentication_password_when_the_user_signs_in_through_sso(): void
    {
        $user = User::factory()->singleSignOn()->create();

        $this->assertEquals('', $user->getAuthPassword());
    }

    #[Test]
    public function it_knows_whether_it_uses_single_sign_on(): void
    {
        $withSso = User::factory()->singleSignOn()->create();
        $withPassword = User::factory()->create();

        $this->assertTrue($withSso->usesSingleSignOn());
        $this->assertFalse($withPassword->usesSingleSignOn());
    }

    #[Test]
    public function it_hides_the_password_hash_when_serialized(): void
    {
        $user = User::factory()->create();

        $this->assertArrayNotHasKey('password_hash', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    #[Test]
    public function it_casts_the_dates_and_the_active_flag(): void
    {
        $user = User::factory()->inactive()->create([
            'last_login_at' => now(),
            'password_changed_at' => '2026-07-31 09:30:00',
        ]);

        $user->refresh();

        $this->assertFalse($user->is_active);
        $this->assertNotNull($user->last_login_at);
        $this->assertNotNull($user->email_verified_at);
        $this->assertInstanceOf(Carbon::class, $user->password_changed_at);
    }

    #[Test]
    public function it_has_no_password_change_date_when_the_password_was_never_set(): void
    {
        $user = User::factory()->singleSignOn()->create();

        $this->assertNull($user->password_changed_at);
    }

    #[Test]
    public function it_soft_deletes(): void
    {
        $user = User::factory()->create();

        $user->delete();

        $this->assertSoftDeleted($user);
    }

    #[Test]
    public function it_knows_whether_the_email_address_was_confirmed(): void
    {
        $this->assertFalse(User::factory()->unverified()->create()->hasConfirmedEmail());
        $this->assertTrue(User::factory()->create()->hasConfirmedEmail());
    }
}
