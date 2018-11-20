<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Photo;
use App\Models\Account\Account;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PhotoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $photo = factory(Photo::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($photo->account()->exists());
    }
}
