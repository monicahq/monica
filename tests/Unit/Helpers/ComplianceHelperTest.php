<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Settings\Term;
use App\Helpers\ComplianceHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ComplianceHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_checks_if_the_user_has_signed_the_given_term()
    {
        $user = factory(User::class)->create([]);
        $term = factory(Term::class)->create([]);
        $this->assertFalse(ComplianceHelper::hasSignedGivenTerm($user, $term));

        $term = factory(Term::class)->create([]);
        $user->terms()->sync([$term->id => ['account_id' => $user->account_id]]);

        $this->assertTrue(ComplianceHelper::hasSignedGivenTerm($user, $term));
    }

    /** @test */
    public function it_checks_if_the_user_has_signed_the_latest_term()
    {
        $user = factory(User::class)->create([]);
        $term = factory(Term::class)->create([
            'created_at' => '1990-02-07 02:26:07',
        ]);
        $this->assertFalse(ComplianceHelper::isCompliantWithCurrentTerm($user));

        $term = factory(Term::class)->create([
            'created_at' => '2020-02-07 02:26:07',
        ]);
        $user->terms()->syncWithoutDetaching([$term->id => ['account_id' => $user->account_id]]);

        $this->assertTrue(ComplianceHelper::isCompliantWithCurrentTerm($user));
    }
}
