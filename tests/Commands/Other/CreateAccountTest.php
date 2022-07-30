<?php

namespace Tests\Commands\Other;

use Tests\TestCase;
use App\Models\User\User;
use App\Console\Commands\CreateAccount;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateAccountTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_account()
    {
        $email = 'user1@example.com';
        $this->artisan('account:create', ['--email' => 'user1@example.com', '--password' => 'astrongpassword'])
            ->run();

        $user = User::where('email', '=', $email)->first();
        $this->assertNotEmpty($user);
    }

    /** @test */
    public function it_creates_account_with_specified_name()
    {
        $email = 'user1@example.com';
        $firstname = 'firstname';
        $lastname = 'lastname';
        $this->artisan('account:create', [
            '--email' => $email,
            '--password' => 'astrongpassword',
            '--firstname' => $firstname,
            '--lastname' => $lastname,
        ])->run();

        $user = User::where('email', '=', $email)->first();
        $this->assertNotEmpty($user);
    }

    /** @test */
    public function it_fails_creation_without_email()
    {
        $this->artisan('account:create', ['--password' => 'astrongpassword'])
            ->expectsOutput(CreateAccount::ERROR_MISSING_EMAIL)
            ->doesntExpectOutput(CreateAccount::ERROR_MISSING_PASSWORD)
            ->run();
    }

    /** @test */
    public function it_fails_creation_without_password()
    {
        $email = 'user1@example.com';
        $this->artisan('account:create', ['--email' => $email])
            ->expectsOutput(CreateAccount::ERROR_MISSING_PASSWORD)
            ->doesntExpectOutput(CreateAccount::ERROR_MISSING_EMAIL)
            ->run();
    }
}
