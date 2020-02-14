<?php

namespace Tests\Unit\Models;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Settings\Term;
use App\Models\Account\Account;
use App\Models\Contact\Reminder;
use App\Models\Settings\Currency;
use App\Services\User\CreateUser;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    private function createUser($account_id, $first_name, $last_name, $email, $password)
    {
        return app(CreateUser::class)->execute([
            'account_id' => $account_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'password' => $password,
        ]);
    }

    /** @test */
    public function it_belongs_to_account()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create(['account_id' => $account->id]);

        $this->assertTrue($user->account()->exists());
    }

    /** @test */
    public function it_belongs_to_many_terms()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $term = factory(Term::class)->create();
        $user->terms()->sync([$term->id => ['account_id' => $account->id]]);

        $user = factory(User::class)->create(['account_id' => $account->id]);
        $term = factory(Term::class)->create();
        $user->terms()->sync([$term->id => ['account_id' => $account->id]]);

        $this->assertTrue($user->terms()->exists());
    }

    /** @test */
    public function name_accessor_returns_name_in_the_user_preferred_way()
    {
        $user = new User;
        $user->first_name = 'John';
        $user->last_name = 'Doe';
        $user->name_order = 'firstname_lastname';

        $this->assertEquals(
            $user->name,
            'John Doe'
        );

        $user->name_order = 'lastname_firstname';

        $this->assertEquals(
            $user->name,
            'Doe John'
        );
    }

    /** @test */
    public function it_gets_2fa_secret_attribute()
    {
        $user = new User;

        $this->assertNull($user->getGoogle2faSecretAttribute(null));

        $string = 'pass1234';

        $this->assertEquals(
            $string,
            $user->getGoogle2faSecretAttribute(encrypt($string))
        );
    }

    /** @test */
    public function it_gets_fluid_layout()
    {
        $user = new User;
        $user->fluid_container = true;

        $this->assertEquals(
            'container-fluid',
            $user->getFluidLayout()
        );

        $user->fluid_container = false;

        $this->assertEquals(
            'container',
            $user->getFluidLayout()
        );
    }

    /** @test */
    public function it_gets_the_locale()
    {
        $user = new User;
        $user->locale = 'en';

        $this->assertEquals(
            'en',
            $user->locale
        );
    }

    /** @test */
    public function user_should_not_be_reminded_because_dates_are_different()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        $account = factory(Account::class)->create();
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $account->id,
            'initial_date' => '2018-02-01',
        ]);

        $this->assertFalse($user->isTheRightTimeToBeReminded($reminder->initial_date));
    }

    /** @test */
    public function user_should_not_be_reminded_because_hours_are_different()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));
        $account = factory(Account::class)->create(['default_time_reminder_is_sent' => '08:00']);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $account->id,
            'initial_date' => '2017-01-01',
        ]);

        $this->assertFalse($user->isTheRightTimeToBeReminded($reminder->initial_date));
    }

    /** @test */
    public function user_should_not_be_reminded_because_timezone_is_different()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0, 'Europe/Berlin'));
        $account = factory(Account::class)->create(['default_time_reminder_is_sent' => '07:00']);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $account->id,
            'initial_date' => '2017-01-01',
        ]);

        $this->assertFalse($user->isTheRightTimeToBeReminded($reminder->initial_date));
    }

    /** @test */
    public function user_should_be_reminded()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 32, 12));
        $account = factory(Account::class)->create(['default_time_reminder_is_sent' => '07:00']);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $account->id,
            'initial_date' => '2017-01-01',
        ]);

        $this->assertTrue($user->isTheRightTimeToBeReminded($reminder->initial_date));
    }

    /** @test */
    public function it_creates_default_user_en()
    {
        App::setLocale('en');

        $account = factory(Account::class)->create([]);
        $user = $this->createUser($account->id, 'John', 'Doe', 'john@doe.com', 'password');
        $currency = Currency::where('iso', 'USD')->first();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@doe.com',
            'locale' => 'en',
            'timezone' => 'America/Chicago',
            'currency_id' => $currency->id,
            'temperature_scale' => 'fahrenheit',
        ]);
    }

    /** @test */
    public function it_creates_default_user_fr()
    {
        App::setLocale('fr');

        $account = factory(Account::class)->create([]);
        $user = $this->createUser($account->id, 'John', 'Doe', 'john@doe.com', 'password');
        $currency = Currency::where('iso', 'EUR')->first();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@doe.com',
            'locale' => 'fr',
            'timezone' => 'Europe/Paris',
            'currency_id' => $currency->id,
            'temperature_scale' => 'celsius',
        ]);
    }

    /** @test */
    public function it_creates_default_user_cs()
    {
        App::setLocale('cs');

        $account = factory(Account::class)->create([]);
        $user = $this->createUser($account->id, 'John', 'Doe', 'john@doe.com', 'password');
        $currency = Currency::where('iso', 'CZK')->first();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@doe.com',
            'locale' => 'cs',
            'timezone' => 'Europe/Prague',
            'currency_id' => $currency->id,
            'temperature_scale' => 'celsius',
        ]);
    }

    /** @test */
    public function it_creates_default_user_de()
    {
        App::setLocale('de');

        $account = factory(Account::class)->create([]);
        $user = $this->createUser($account->id, 'John', 'Doe', 'john@doe.com', 'password');
        $currency = Currency::where('iso', 'EUR')->first();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@doe.com',
            'locale' => 'de',
            'timezone' => 'Europe/Berlin',
            'currency_id' => $currency->id,
            'temperature_scale' => 'celsius',
        ]);
    }

    /** @test */
    public function it_creates_default_user_es()
    {
        App::setLocale('es');

        $account = factory(Account::class)->create([]);
        $user = $this->createUser($account->id, 'John', 'Doe', 'john@doe.com', 'password');
        $currency = Currency::where('iso', 'EUR')->first();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@doe.com',
            'locale' => 'es',
            'timezone' => 'Europe/Madrid',
            'currency_id' => $currency->id,
            'temperature_scale' => 'celsius',
        ]);
    }

    /** @test */
    public function it_creates_default_user_he()
    {
        App::setLocale('he');

        $account = factory(Account::class)->create([]);
        $user = $this->createUser($account->id, 'John', 'Doe', 'john@doe.com', 'password');
        $currency = Currency::where('iso', 'ILS')->first();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@doe.com',
            'locale' => 'he',
            'timezone' => 'Asia/Jerusalem',
            'currency_id' => $currency->id,
            'temperature_scale' => 'celsius',
        ]);
    }

    /** @test */
    public function it_creates_default_user_it()
    {
        App::setLocale('it');

        $account = factory(Account::class)->create([]);
        $user = $this->createUser($account->id, 'John', 'Doe', 'john@doe.com', 'password');
        $currency = Currency::where('iso', 'EUR')->first();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@doe.com',
            'locale' => 'it',
            'timezone' => 'Europe/Rome',
            'currency_id' => $currency->id,
            'temperature_scale' => 'celsius',
        ]);
    }

    /** @test */
    public function it_creates_default_user_nl()
    {
        App::setLocale('nl');

        $account = factory(Account::class)->create([]);
        $user = $this->createUser($account->id, 'John', 'Doe', 'john@doe.com', 'password');
        $currency = Currency::where('iso', 'EUR')->first();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@doe.com',
            'locale' => 'nl',
            'timezone' => 'Europe/Amsterdam',
            'currency_id' => $currency->id,
            'temperature_scale' => 'celsius',
        ]);
    }

    /** @test */
    public function it_creates_default_user_pt()
    {
        App::setLocale('pt');

        $account = factory(Account::class)->create([]);
        $user = $this->createUser($account->id, 'John', 'Doe', 'john@doe.com', 'password');
        $currency = Currency::where('iso', 'EUR')->first();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@doe.com',
            'locale' => 'pt',
            'timezone' => 'Europe/Lisbon',
            'currency_id' => $currency->id,
            'temperature_scale' => 'celsius',
        ]);
    }

    /** @test */
    public function it_creates_default_user_ru()
    {
        App::setLocale('ru');

        $account = factory(Account::class)->create([]);
        $user = $this->createUser($account->id, 'John', 'Doe', 'john@doe.com', 'password');
        $currency = Currency::where('iso', 'RUB')->first();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@doe.com',
            'locale' => 'ru',
            'timezone' => 'Europe/Moscow',
            'currency_id' => $currency->id,
            'temperature_scale' => 'celsius',
        ]);
    }

    /** @test */
    public function it_creates_default_user_zh()
    {
        App::setLocale('zh');

        $account = factory(Account::class)->create([]);
        $user = $this->createUser($account->id, 'John', 'Doe', 'john@doe.com', 'password');
        $currency = Currency::where('iso', 'CNY')->first();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@doe.com',
            'locale' => 'zh',
            'timezone' => 'Asia/Shanghai',
            'currency_id' => $currency->id,
            'temperature_scale' => 'celsius',
        ]);
    }

    /** @test */
    public function it_sends_a_verification_email()
    {
        config(['monica.signup_double_optin' => true]);
        Notification::fake();

        $user = factory(User::class)->create([]);
        $user->sendEmailVerificationNotification();

        Notification::assertSentTo(
            [$user], VerifyEmail::class
        );
    }

    /** @test */
    public function it_doesnt_send_a_verification_email_if_the_double_optin_is_disabled_at_the_instance_level()
    {
        config(['monica.signup_double_optin' => false]);
        Notification::fake();

        $user = factory(User::class)->create([]);
        $user->sendEmailVerificationNotification();

        Notification::assertNothingSent();
    }
}
