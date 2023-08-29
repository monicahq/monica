<?php

namespace Tests\Unit\Domains\Settings\ManageGenders\Web\ViewHelpers;

use App\Domains\Settings\ManageGenders\Web\ViewHelpers\ManageGenderIndexViewHelper;
use App\Models\Gender;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class ManageGenderIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $gender = Gender::factory()->create();
        $array = ManageGenderIndexViewHelper::data($gender->account);
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
        $array = ManageGenderIndexViewHelper::dtoGender($gender);
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
