<?php

namespace Tests\Unit\Domains\Settings\ManageReligion\Web\ViewHelpers;

use App\Domains\Settings\ManageReligion\Web\ViewHelpers\PersonalizeReligionViewHelper;
use App\Models\Religion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class PersonalizeReligionViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $religion = Religion::factory()->create();
        $array = PersonalizeReligionViewHelper::data($religion->account);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertArrayHasKey('religions', $array);
        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'personalize' => env('APP_URL').'/settings/personalize',
                'store' => env('APP_URL').'/settings/personalize/religions',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $religion = Religion::factory()->create();
        $array = PersonalizeReligionViewHelper::dto($religion);
        $this->assertEquals(
            [
                'id' => $religion->id,
                'name' => $religion->name,
                'position' => $religion->position,
                'url' => [
                    'position' => env('APP_URL').'/settings/personalize/religions/'.$religion->id.'/position',
                    'update' => env('APP_URL').'/settings/personalize/religions/'.$religion->id,
                    'destroy' => env('APP_URL').'/settings/personalize/religions/'.$religion->id,
                ],
            ],
            $array
        );
    }
}
