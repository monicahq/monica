<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Tests\TestCase;
use App\Models\Contact\Gift;
use App\Models\Account\Account;
use App\Models\Account\Photo;
use App\Models\Contact\Contact;
use App\Services\Contact\Gift\AssociateGift;
use App\Services\Contact\Gift\AssociatePhotoToGift;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AssociateGiftTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_associates_a_photo_to_a_gift()
    {
        $gift = factory(Gift::class)->create();
        $photo = factory(Photo::class)->create([
            'account_id' => $gift->account_id,
        ]);

        app(AssociatePhotoToGift::class)->execute([
            'account_id' => $gift->account_id,
            'gift_id' => $gift->id,
            'photo_id' => $photo->id,
        ]);

        $this->assertDatabaseHas('gift_photo', [
            'gift_id' => $gift->id,
            'photo_id' => $photo->id,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $this->expectException(ValidationException::class);

        app(AssociatePhotoToGift::class)->execute([
            'account_id' => -1,
            'gift_id' => -1,
            'photo_id' => -1,
        ]);
    }

    public function test_it_fails_if_photo_is_wrong_account()
    {
        $gift = factory(Gift::class)->create();
        $photo = factory(Photo::class)->create();

        $this->expectException(ModelNotFoundException::class);

        app(AssociatePhotoToGift::class)->execute([
            'account_id' => $gift->account_id,
            'gift_id' => $gift->id,
            'photo_id' => $photo->id,
        ]);
    }
}
