<?php

namespace Tests\Unit\Services\Contact\Gift;

use Tests\TestCase;
use App\Models\Contact\Gift;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Services\Contact\Gift\CreateGift;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreateGiftTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_gift()
    {
        $contact = factory(Contact::class)->create();

        $gift = app(CreateGift::class)->execute([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'name' => 'Book',
            'status' => 'idea',
        ]);

        $this->assertDatabaseHas('gifts', [
            'id' => $gift->id,
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'name' => 'Book',
            'status' => 'idea',
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

        app(CreateGift::class)->execute([
            'account_id' => -1,
            'contact_id' => -1,
            'name' => 'Book',
            'status' => 'idea',
        ]);
    }

    /** @test */
    public function it_fails_if_contact_is_wrong_account()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create();

        $this->expectException(ModelNotFoundException::class);

        $gift = app(CreateGift::class)->execute([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'name' => 'Book',
            'status' => 'idea',
        ]);
    }
}
