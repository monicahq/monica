<?php

namespace Tests\Unit\Domains\Settings\ManageGiftStates\Web\ViewHelpers;

use App\Domains\Settings\ManageGiftStates\Web\ViewHelpers\PersonalizeGiftStateViewHelper;
use App\Models\GiftState;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class PersonalizeGiftStateViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $giftState = GiftState::factory()->create();
        $array = PersonalizeGiftStateViewHelper::data($giftState->account);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertArrayHasKey('gift_states', $array);
        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'personalize' => env('APP_URL').'/settings/personalize',
                'store' => env('APP_URL').'/settings/personalize/giftStates',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $giftState = GiftState::factory()->create();
        $array = PersonalizeGiftStateViewHelper::dto($giftState);
        $this->assertEquals(
            [
                'id' => $giftState->id,
                'label' => $giftState->label,
                'position' => $giftState->position,
                'url' => [
                    'position' => env('APP_URL').'/settings/personalize/giftStates/'.$giftState->id.'/position',
                    'update' => env('APP_URL').'/settings/personalize/giftStates/'.$giftState->id,
                    'destroy' => env('APP_URL').'/settings/personalize/giftStates/'.$giftState->id,
                ],
            ],
            $array
        );
    }
}
