<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\DateFormatEnum;
use App\Enums\NameOrderEnum;
use App\Enums\NumberFormatEnum;
use App\Models\Account;
use App\Models\EmailSent;
use App\Models\User;
use App\Models\WebauthnKey;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
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
    public function it_is_the_only_user_of_its_account(): void
    {
        $account = Account::factory()->create();
        User::factory()->create(['account_id' => $account->id]);

        $this->expectException(QueryException::class);

        User::factory()->create(['account_id' => $account->id]);
    }

    #[Test]
    public function it_gives_the_name_the_user_is_called_by(): void
    {
        $user = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);

        $this->assertEquals('Michael Scott', $user->name);
    }

    #[Test]
    public function it_gives_the_name_family_name_first_when_the_user_asked_for_it(): void
    {
        $user = User::factory()->familyNameFirst()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        $this->assertEquals('Scott Michael', $user->name);
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
    public function it_authenticates_against_the_password_column(): void
    {
        $user = User::factory()->create(['password' => Hash::make('dwightschrute')]);

        $this->assertEquals($user->password, $user->getAuthPassword());
        $this->assertTrue(Hash::check('dwightschrute', $user->getAuthPassword()));
    }

    #[Test]
    public function it_has_no_authentication_password_when_the_user_signs_in_through_sso(): void
    {
        $user = User::factory()->singleSignOn()->create();

        $this->assertNull($user->getAuthPassword());
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
    public function it_hides_the_password_when_serialized(): void
    {
        $user = User::factory()->create();

        $this->assertArrayNotHasKey('password', $user->toArray());
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
    public function it_carries_the_preferences_that_decide_how_things_are_rendered(): void
    {
        $user = User::factory()->create([
            'locale' => 'fr_FR',
            'timezone' => 'America/New_York',
            'date_format' => DateFormatEnum::DayFirst,
            'number_format' => NumberFormatEnum::SpaceAndComma,
            'name_order' => NameOrderEnum::FamilyFirst,
        ]);

        $user->refresh();

        $this->assertEquals('fr_FR', $user->locale);
        $this->assertEquals('America/New_York', $user->timezone);
        $this->assertEquals(DateFormatEnum::DayFirst, $user->date_format);
        $this->assertEquals(NumberFormatEnum::SpaceAndComma, $user->number_format);
        $this->assertEquals(NameOrderEnum::FamilyFirst, $user->name_order);
    }

    #[Test]
    public function it_falls_back_to_the_time_zone_of_the_instance_and_to_the_given_name_first(): void
    {
        $user = User::factory()->create();

        $user->refresh();

        $this->assertEquals('UTC', $user->timezone);
        $this->assertEquals(NameOrderEnum::GivenFirst, $user->name_order);
        $this->assertNull($user->date_format);
        $this->assertNull($user->number_format);
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

    #[Test]
    public function it_has_passkeys(): void
    {
        $user = User::factory()->create();
        WebauthnKey::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertTrue($user->passkeys()->exists());
        $this->assertCount(2, $user->passkeys);
    }

    #[Test]
    public function it_knows_whether_it_holds_a_passkey(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasPasskeys());

        WebauthnKey::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->hasPasskeys());
    }

    #[Test]
    public function it_knows_a_passwordless_account_from_an_ordinary_one(): void
    {
        $this->assertFalse(User::factory()->create()->usesPasswordlessSignIn());
        $this->assertTrue(User::factory()->create(['password' => null])->usesPasswordlessSignIn());
    }

    #[Test]
    public function it_does_not_call_a_single_sign_on_account_passwordless(): void
    {
        $user = User::factory()->singleSignOn()->create();

        $this->assertNull($user->password);
        $this->assertTrue($user->usesSingleSignOn());
        $this->assertFalse($user->usesPasswordlessSignIn());
    }
}
