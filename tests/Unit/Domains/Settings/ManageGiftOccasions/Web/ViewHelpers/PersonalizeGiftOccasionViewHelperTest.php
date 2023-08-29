<?php

namespace Tests\Unit\Domains\Settings\ManageGiftOccasions\Web\ViewHelpers;

use App\Domains\Settings\ManageGiftOccasions\Web\ViewHelpers\PersonalizeGiftOccasionViewHelper;
use App\Models\GiftOccasion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class PersonalizeGiftOccasionViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $giftOccasion = GiftOccasion::factory()->create();
        $array = PersonalizeGiftOccasionViewHelper::data($giftOccasion->account);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertArrayHasKey('gift_occasions', $array);
        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'personalize' => env('APP_URL').'/settings/personalize',
                'store' => env('APP_URL').'/settings/personalize/giftOccasions',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $giftOccasion = GiftOccasion::factory()->create();
        $array = PersonalizeGiftOccasionViewHelper::dto($giftOccasion);
        $this->assertEquals(
            [
                'id' => $giftOccasion->id,
                'label' => $giftOccasion->label,
                'position' => $giftOccasion->position,
                'url' => [
                    'position' => env('APP_URL').'/settings/personalize/giftOccasions/'.$giftOccasion->id.'/position',
                    'update' => env('APP_URL').'/settings/personalize/giftOccasions/'.$giftOccasion->id,
                    'destroy' => env('APP_URL').'/settings/personalize/giftOccasions/'.$giftOccasion->id,
                ],
            ],
            $array
        );
    }
}
