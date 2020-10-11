<?php

namespace Tests\Unit\Services\Contact\Gift;

use Tests\TestCase;
use App\Models\Contact\Gift;
use App\Models\Account\Account;
use App\Services\Contact\Gift\DestroyGift;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyGiftTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_gift()
    {
        $gift = factory(Gift::class)->create();

        $this->assertDatabaseHas('gifts', [
            'account_id' => $gift->account_id,
            'contact_id' => $gift->contact_id,
            'id' => $gift->id,
        ]);

        app(DestroyGift::class)->execute([
            'account_id' => $gift->account_id,
            'gift_id' => $gift->id,
        ]);

        $this->assertDatabaseMissing('gifts', [
            'id' => $gift->id,
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $this->expectException(ValidationException::class);

        app(DestroyGift::class)->execute([
            'account_id' => -1,
        ]);
    }

    /** @test */
    public function it_fails_if_gift_is_wrong_account()
    {
        $account = factory(Account::class)->create();
        $gift = factory(Gift::class)->create();

        $this->expectException(ModelNotFoundException::class);

        app(DestroyGift::class)->execute([
            'account_id' => $account->id,
            'gift_id' => $gift->id,
        ]);
    }
}
