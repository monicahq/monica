<?php

namespace Tests\Unit\Controllers\Settings\Personalize\Genders\ViewHelpers;

use function env;
use Tests\TestCase;
use App\Models\Gender;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\Settings\Personalize\Genders\ViewHelpers\PersonalizeGenderIndexViewHelper;

class PersonalizeGenderIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $gender = Gender::factory()->create();
        $array = PersonalizeGenderIndexViewHelper::data($gender->account);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertArrayHasKey('genders', $array);
        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'personalize' => env('APP_URL').'/settings/personalize',
                'gender_store' => env('APP_URL').'/settings/personalize/genders',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $gender = Gender::factory()->create();
        $array = PersonalizeGenderIndexViewHelper::dtoGender($gender);
        $this->assertEquals(
            [
                'id' => $gender->id,
                'name' => $gender->name,
                'url' => [
                    'update' => env('APP_URL').'/settings/personalize/genders/'.$gender->id,
                    'destroy' => env('APP_URL').'/settings/personalize/genders/'.$gender->id,
                ],
            ],
            $array
        );
    }
}
