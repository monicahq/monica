<?php

namespace Tests\Unit\Models;

use App\Models\Account\Account;
use App\Models\Settings\Term;
use App\Models\User\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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
