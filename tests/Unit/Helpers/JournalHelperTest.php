<?php

namespace Tests\Unit\Helpers;

use App\Helpers\JournalHelper;
use App\Models\Account\Account;
use App\Models\Journal\Day;
use App\Models\User\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class JournalHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function you_can_vote_if_you_havent_voted_yet_today()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create(['account_id' => $account->id]);

        $this->assertFalse(JournalHelper::hasAlreadyRatedToday($user));
    }

    /** @test */
    public function you_cant_vote_if_you_have_already_voted_today()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        factory(Day::class)->create([
            'account_id' => $account->id,
            'date' => now(),
        ]);

        $this->assertTrue(JournalHelper::hasAlreadyRatedToday($user));
    }
}
