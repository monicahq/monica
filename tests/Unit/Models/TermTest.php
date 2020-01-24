<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Settings\Term;
use App\Models\Account\Account;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TermTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_many_users()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $term = factory(Term::class)->create([]);
        $term->users()->sync([$user->id => ['account_id' => $account->id]]);

        $user = factory(User::class)->create(['account_id' => $account->id]);
        $term = factory(Term::class)->create([]);
        $term->users()->sync([$user->id => ['account_id' => $account->id]]);

        $this->assertTrue($term->users()->exists());
    }
}
