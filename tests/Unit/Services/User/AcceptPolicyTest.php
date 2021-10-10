<?php

namespace Tests\Unit\Services\User;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Settings\Term;
use App\Models\Account\Account;
use App\Services\User\AcceptPolicy;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AcceptPolicyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_accepts_the_policy()
    {
        $user = factory(User::class)->create([]);
        $term = factory(Term::class)->create([]);

        $request = [
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'ip_address' => '182.21.12.21',
        ];

        $term = app(AcceptPolicy::class)->execute($request);

        $this->assertDatabaseHas('term_user', [
            'user_id' => $user->id,
            'term_id' => $term->id,
            'account_id' => $user->account_id,
            'ip_address' => '182.21.12.21',
        ]);

        $this->assertInstanceOf(
            Term::class,
            $term
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $user = factory(User::class)->create([]);

        $request = [
            'email' => 'email@email.com',
        ];

        $this->expectException(ValidationException::class);
        app(AcceptPolicy::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_user_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create();
        $user = factory(User::class)->create();

        $request = [
            'account_id' => $account->id,
            'user_id' => $user->id,
            'ip_address' => '182.21.12.21',
        ];

        $this->expectException(ModelNotFoundException::class);
        app(AcceptPolicy::class)->execute($request);
    }
}
