<?php

namespace Tests\Unit\Domains\Settings\ManageCallReasons\Web\ViewHelpers;

use App\Domains\Settings\ManageCallReasons\Web\ViewHelpers\PersonalizeCallReasonsIndexViewHelper;
use App\Models\CallReason;
use App\Models\CallReasonType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class PersonalizeCallReasonsIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $type = CallReasonType::factory()->create();
        $reason = CallReason::factory()->create([
            'call_reason_type_id' => $type->id,
        ]);

        $array = PersonalizeCallReasonsIndexViewHelper::data($type->account);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertArrayHasKey('call_reason_types', $array);
        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'personalize' => env('APP_URL').'/settings/personalize',
                'call_reason_type_store' => env('APP_URL').'/settings/personalize/callReasonTypes',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_dto_for_reason_type(): void
    {
        $type = CallReasonType::factory()->create();

        $array = PersonalizeCallReasonsIndexViewHelper::dtoReasonType($type);
        $this->assertEquals(
            4,
            count($array)
        );
        $this->assertArrayHasKey('reasons', $array);
        $this->assertEquals(
            $type->label,
            $array['label']
        );
        $this->assertEquals(
            [
                'store' => env('APP_URL').'/settings/personalize/callReasonTypes/'.$type->id.'/reasons',
                'update' => env('APP_URL').'/settings/personalize/callReasonTypes/'.$type->id,
                'destroy' => env('APP_URL').'/settings/personalize/callReasonTypes/'.$type->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_dto_for_reason(): void
    {
        $type = CallReasonType::factory()->create();
        $reason = CallReason::factory()->create([
            'call_reason_type_id' => $type->id,
        ]);

        $array = PersonalizeCallReasonsIndexViewHelper::dtoReason($type, $reason);
        $this->assertEquals(
            3,
            count($array)
        );
        $this->assertEquals(
            [
                'id' => $reason->id,
                'label' => $reason->label,
                'url' => [
                    'update' => env('APP_URL').'/settings/personalize/callReasonTypes/'.$type->id.'/reasons/'.$reason->id,
                    'destroy' => env('APP_URL').'/settings/personalize/callReasonTypes/'.$type->id.'/reasons/'.$reason->id,
                ],
            ],
            $array
        );
    }
}
