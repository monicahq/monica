<?php

namespace Tests\Unit\Domains\Settings\ManagePetCategories\Web\ViewHelpers;

use App\Domains\Settings\ManagePetCategories\Web\ViewHelpers\PersonalizePetCategoriesIndexViewHelper;
use App\Models\PetCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class PersonalizePetCategoriesIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $petCategory = PetCategory::factory()->create();
        $array = PersonalizePetCategoriesIndexViewHelper::data($petCategory->account);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertArrayHasKey('pet_categories', $array);
        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'personalize' => env('APP_URL').'/settings/personalize',
                'pet_category_store' => env('APP_URL').'/settings/personalize/petCategories',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $petCategory = PetCategory::factory()->create();
        $array = PersonalizePetCategoriesIndexViewHelper::dtoPetCategory($petCategory);
        $this->assertEquals(
            [
                'id' => $petCategory->id,
                'name' => $petCategory->name,
                'url' => [
                    'update' => env('APP_URL').'/settings/personalize/petCategories/'.$petCategory->id,
                    'destroy' => env('APP_URL').'/settings/personalize/petCategories/'.$petCategory->id,
                ],
            ],
            $array
        );
    }
}
