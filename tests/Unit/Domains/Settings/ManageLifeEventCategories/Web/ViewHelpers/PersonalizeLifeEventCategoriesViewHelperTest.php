<?php

namespace Tests\Unit\Domains\Settings\ManageLifeEventCategories\Web\ViewHelpers;

use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Settings\ManageLifeEventCategories\Web\ViewHelpers\PersonalizeLifeEventCategoriesViewHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use function env;

class PersonalizeLifeEventCategoriesViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $lifeEventCategory = LifeEventCategory::factory()->create();
        LifeEventType::factory()->create([
            'life_event_category_id' => $lifeEventCategory->id,
        ]);

        $array = PersonalizeLifeEventCategoriesViewHelper::data($lifeEventCategory->account);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertArrayHasKey('life_event_categories', $array);
        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'personalize' => env('APP_URL').'/settings/personalize',
                'store' => env('APP_URL').'/settings/personalize/lifeEventCategories',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_dto_for_life(): void
    {
        $lifeEventCategory = LifeEventCategory::factory()->create();

        $array = PersonalizeLifeEventCategoriesViewHelper::dtoLifeEventCategory($lifeEventCategory);
        $this->assertEquals(
            6,
            count($array)
        );
        $this->assertArrayHasKey('life_event_types', $array);
        $this->assertEquals(
            $lifeEventCategory->label,
            $array['label']
        );
        $this->assertEquals(
            $lifeEventCategory->can_be_deleted,
            $array['can_be_deleted']
        );
        $this->assertEquals(
            null,
            $array['type']
        );
        $this->assertEquals(
            [
                'store' => env('APP_URL').'/settings/personalize/lifeEventCategories/'.$lifeEventCategory->id.'/lifeEventTypes',
                'update' => env('APP_URL').'/settings/personalize/lifeEventCategories/'.$lifeEventCategory->id,
                'destroy' => env('APP_URL').'/settings/personalize/lifeEventCategories/'.$lifeEventCategory->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_dto_for_life_event_type(): void
    {
        $lifeEventCategory = LifeEventCategory::factory()->create();
        $lifeEventType = LifeEventType::factory()->create([
            'life_event_category_id' => $lifeEventCategory->id,
        ]);

        $array = PersonalizeLifeEventCategoriesViewHelper::dtoType($lifeEventCategory, $lifeEventType);
        $this->assertEquals(
            6,
            count($array)
        );
        $this->assertEquals(
            [
                'id' => $lifeEventType->id,
                'label' => $lifeEventType->label,
                'can_be_deleted' => $lifeEventType->can_be_deleted,
                'type' => null,
                'position' => 1,
                'url' => [
                    'position' => env('APP_URL').'/settings/personalize/lifeEventCategories/'.$lifeEventCategory->id.'/lifeEventTypes/'.$lifeEventType->id.'/order',
                    'update' => env('APP_URL').'/settings/personalize/lifeEventCategories/'.$lifeEventCategory->id.'/lifeEventTypes/'.$lifeEventType->id,
                    'destroy' => env('APP_URL').'/settings/personalize/lifeEventCategories/'.$lifeEventCategory->id.'/lifeEventTypes/'.$lifeEventType->id,
                ],
            ],
            $array
        );
    }
}
