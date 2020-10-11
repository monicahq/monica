<?php

namespace Tests\Unit\Services\Contact\Gift;

use Tests\TestCase;
use App\Models\Contact\Gift;
use App\Models\Account\Account;
use App\Services\Contact\Gift\UpdateGift;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateGiftTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_gift()
    {
        $gift = factory(Gift::class)->create();

        $gift = app(UpdateGift::class)->execute([
            'account_id' => $gift->account_id,
            'gift_id' => $gift->id,
            'contact_id' => $gift->contact_id,
            'name' => 'Book',
            'status' => 'offered',
        ]);

        $this->assertDatabaseHas('gifts', [
            'id' => $gift->id,
            'name' => 'Book',
            'status' => 'offered',
        ]);

        $this->assertInstanceOf(
            Gift::class,
            $gift
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $this->expectException(ValidationException::class);

        app(UpdateGift::class)->execute([
            'account_id' => -1,
            'gift_id' => -1,
        ]);
    }

    /** @test */
    public function it_throws_an_exception_if_gift_wrong_account()
    {
        $account = factory(Account::class)->create();
        $gift = factory(Gift::class)->create();

        $this->expectException(ModelNotFoundException::class);

        app(UpdateGift::class)->execute([
            'account_id' => $account->id,
            'gift_id' => $gift->id,
            'contact_id' => $gift->contact_id,
            'name' => 'Book',
            'status' => 'offered',
        ]);
    }
}
