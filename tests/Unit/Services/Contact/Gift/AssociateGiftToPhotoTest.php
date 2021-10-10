<?php

namespace Tests\Unit\Services\Contact\Gift;

use Tests\TestCase;
use App\Models\Contact\Gift;
use App\Models\Account\Photo;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Gift\AssociatePhotoToGift;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AssociateGiftToPhotoTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_associates_a_photo_to_a_gift()
    {
        $gift = factory(Gift::class)->create();
        $photo = factory(Photo::class)->create([
            'account_id' => $gift->account_id,
        ]);

        $giftUpdated = app(AssociatePhotoToGift::class)->execute([
            'account_id' => $gift->account_id,
            'gift_id' => $gift->id,
            'photo_id' => $photo->id,
        ]);

        $this->assertInstanceOf(Gift::class, $giftUpdated);
        $this->assertEquals($gift->id, $giftUpdated->id);

        $this->assertDatabaseHas('gift_photo', [
            'gift_id' => $gift->id,
            'photo_id' => $photo->id,
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $this->expectException(ValidationException::class);

        app(AssociatePhotoToGift::class)->execute([
            'account_id' => -1,
            'gift_id' => -1,
            'photo_id' => -1,
        ]);
    }

    /** @test */
    public function it_fails_if_photo_is_wrong_account()
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
